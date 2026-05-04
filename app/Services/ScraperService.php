<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class ScraperService
{
    // Minimum word count before we fall back to Puppeteer microservice
    private const MIN_WORD_COUNT = 80;

    // -----------------------------------------------------------------------
    // Public entry point
    // -----------------------------------------------------------------------

    /**
     * Scrape the business website, enrich with PageSpeed data,
     * then persist everything into business.scraped_data.
     *
     * Returns the populated scraped_data array.
     */
    public function scrape(Business $business): array
    {
        $url = $business->website;

        if (empty($url)) {
            throw new \RuntimeException("Business #{$business->id} has no website URL.");
        }

        $url = $this->normaliseUrl($url);

        // ------------------------------------------------------------------
        // 1. Fetch raw HTML
        // ------------------------------------------------------------------
        $html = $this->fetchHtml($url);

        // If PHP fetch returned thin content, try Puppeteer microservice
        if ($this->isThinContent($html)) {
            $html = $this->fetchViaPuppeteer($url) ?? $html;
        }

        // ------------------------------------------------------------------
        // 2. Parse DOM
        // ------------------------------------------------------------------
        $parsed = $this->parseHtml($html, $url);

        // ------------------------------------------------------------------
        // 3. PageSpeed Insights (non-blocking — null on error)
        // ------------------------------------------------------------------
        $pageSpeed = $this->fetchPageSpeed($url);

        // ------------------------------------------------------------------
        // 4. Extract email from page if not already known
        // ------------------------------------------------------------------
        $emailsFound = $this->extractEmails($html);
        $primaryEmail = $emailsFound[0] ?? null;

        if ($primaryEmail && empty($business->email)) {
            $business->update(['email' => $primaryEmail]);
        }

        // ------------------------------------------------------------------
        // 5. Build final scraped_data blob
        // ------------------------------------------------------------------
        $scrapedData = array_merge($parsed, [
            'page_speed'  => $pageSpeed,
            'emails_found'=> $emailsFound,
            'scraped_at'  => now()->toISOString(),
            'scraped_url' => $url,
        ]);

        $business->update([
            'scraped_data'   => $scrapedData,
            'facebook_url'   => $parsed['social_links']['facebook']  ?? $business->facebook_url,
            'instagram_url'  => $parsed['social_links']['instagram'] ?? $business->instagram_url,
            'linkedin_url'   => $parsed['social_links']['linkedin']  ?? $business->linkedin_url,
            'twitter_url'    => $parsed['social_links']['twitter']   ?? $business->twitter_url,
            'status'         => 'scraped',
        ]);

        return $scrapedData;
    }

    // -----------------------------------------------------------------------
    // HTML fetch (PHP / Guzzle)
    // -----------------------------------------------------------------------

    private function fetchHtml(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; BusinessAuditBot/1.0)',
                'Accept'     => 'text/html,application/xhtml+xml',
            ])
            ->timeout(20)
            ->get($url);

            return $response->successful() ? $response->body() : '';
        } catch (\Throwable $e) {
            Log::warning("ScraperService: HTTP fetch failed for {$url}", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    // -----------------------------------------------------------------------
    // HTML fetch (Puppeteer microservice fallback)
    // -----------------------------------------------------------------------

    private function fetchViaPuppeteer(string $url): ?string
    {
        $serviceUrl = config('audit.scraper.url');
        $secret     = config('audit.scraper.secret');

        if (empty($serviceUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders(['X-Secret' => $secret])
                ->timeout(config('audit.scraper.timeout', 30))
                ->post("{$serviceUrl}/scrape", ['url' => $url]);

            if ($response->successful()) {
                return $response->json('html');
            }
        } catch (\Throwable $e) {
            Log::warning("ScraperService: Puppeteer fallback failed for {$url}", [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // DOM parsing
    // -----------------------------------------------------------------------

    private function parseHtml(string $html, string $baseUrl): array
    {
        if (empty($html)) {
            return $this->emptyParsedData();
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        return [
            'title'             => $this->extractTitle($xpath),
            'meta_description'  => $this->extractMetaDescription($xpath),
            'h1'                => $this->extractFirstTag($xpath, 'h1'),
            'h2s'               => $this->extractAllTags($xpath, 'h2'),
            'h3s'               => $this->extractAllTags($xpath, 'h3'),
            'word_count'        => $this->countWords($html),
            'has_ssl'           => str_starts_with($baseUrl, 'https://'),
            'has_contact_form'  => $this->hasContactForm($xpath),
            'has_cta_button'    => $this->hasCtaButton($xpath),
            'has_phone'         => $this->hasPhone($html),
            'images_count'      => $this->countImages($xpath),
            'internal_links'    => $this->countLinks($xpath, $baseUrl, internal: true),
            'external_links'    => $this->countLinks($xpath, $baseUrl, internal: false),
            'social_links'      => $this->extractSocialLinks($xpath),
            'og_title'          => $this->extractMetaProperty($xpath, 'og:title'),
            'og_description'    => $this->extractMetaProperty($xpath, 'og:description'),
            'canonical_url'     => $this->extractLinkRel($xpath, 'canonical'),
            'robots_meta'       => $this->extractMetaName($xpath, 'robots'),
            'viewport_meta'     => $this->extractMetaName($xpath, 'viewport'),
            'schema_types'      => $this->extractSchemaTypes($html),
        ];
    }

    // -----------------------------------------------------------------------
    // PageSpeed Insights API
    // -----------------------------------------------------------------------

    private function fetchPageSpeed(string $url): ?array
    {
        $apiKey  = config('audit.pagespeed.api_key');
        $baseUrl = config('audit.pagespeed.base_url');

        if (empty($apiKey)) {
            return null;
        }

        $results = [];

        foreach (['mobile', 'desktop'] as $strategy) {
            try {
                $response = Http::timeout(30)->get($baseUrl, [
                    'url'      => $url,
                    'key'      => $apiKey,
                    'strategy' => $strategy,
                    'category' => 'performance',
                ]);

                if ($response->successful()) {
                    $data       = $response->json();
                    $categories = $data['lighthouseResult']['categories'] ?? [];
                    $audits     = $data['lighthouseResult']['audits'] ?? [];

                    $results[$strategy] = [
                        'performance_score' => (int) round(
                            ($categories['performance']['score'] ?? 0) * 100
                        ),
                        'fcp_ms'  => $audits['first-contentful-paint']['numericValue'] ?? null,
                        'lcp_ms'  => $audits['largest-contentful-paint']['numericValue'] ?? null,
                        'tbt_ms'  => $audits['total-blocking-time']['numericValue'] ?? null,
                        'cls'     => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("ScraperService: PageSpeed [{$strategy}] failed", [
                    'url'   => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return empty($results) ? null : $results;
    }

    // -----------------------------------------------------------------------
    // Individual DOM extractors
    // -----------------------------------------------------------------------

    private function extractTitle(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//title');
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : null;
    }

    private function extractMetaDescription(DOMXPath $xpath): ?string
    {
        $node = $xpath->query('//meta[@name="description"]/@content')->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    private function extractFirstTag(DOMXPath $xpath, string $tag): ?string
    {
        $nodes = $xpath->query("//{$tag}");
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : null;
    }

    private function extractAllTags(DOMXPath $xpath, string $tag): array
    {
        $results = [];
        foreach ($xpath->query("//{$tag}") as $node) {
            $text = trim($node->textContent);
            if ($text) {
                $results[] = $text;
            }
        }
        return array_slice($results, 0, 10); // cap at 10
    }

    private function extractMetaProperty(DOMXPath $xpath, string $property): ?string
    {
        $node = $xpath->query("//meta[@property=\"{$property}\"]/@content")->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    private function extractMetaName(DOMXPath $xpath, string $name): ?string
    {
        $node = $xpath->query("//meta[@name=\"{$name}\"]/@content")->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    private function extractLinkRel(DOMXPath $xpath, string $rel): ?string
    {
        $node = $xpath->query("//link[@rel=\"{$rel}\"]/@href")->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    private function hasContactForm(DOMXPath $xpath): bool
    {
        // Look for <form> that contains an input[type=email] or name/id containing 'contact'
        $forms = $xpath->query('//form');
        foreach ($forms as $form) {
            $formHtml = $form->ownerDocument->saveHTML($form);
            if (
                stripos($formHtml, 'type="email"') !== false ||
                stripos($formHtml, 'contact') !== false ||
                stripos($formHtml, 'message') !== false
            ) {
                return true;
            }
        }
        return false;
    }

    private function hasCtaButton(DOMXPath $xpath): bool
    {
        $ctaKeywords = ['book', 'call', 'contact', 'get started', 'free', 'quote', 'appointment', 'schedule'];

        $buttons = $xpath->query('//a | //button');
        foreach ($buttons as $node) {
            $text = strtolower(trim($node->textContent));
            foreach ($ctaKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasPhone(string $html): bool
    {
        return (bool) preg_match('/(\+?\d[\d\s\-().]{7,}\d)/', strip_tags($html));
    }

    private function countWords(string $html): int
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return str_word_count($text);
    }

    private function countImages(DOMXPath $xpath): int
    {
        return $xpath->query('//img')->length;
    }

    private function countLinks(DOMXPath $xpath, string $baseUrl, bool $internal): int
    {
        $host  = parse_url($baseUrl, PHP_URL_HOST);
        $count = 0;

        foreach ($xpath->query('//a[@href]') as $node) {
            $href      = $node->getAttribute('href');
            $linkHost  = parse_url($href, PHP_URL_HOST);

            $isInternal = empty($linkHost) || $linkHost === $host;

            if ($internal === $isInternal) {
                $count++;
            }
        }

        return $count;
    }

    private function extractSocialLinks(DOMXPath $xpath): array
    {
        $map = [
            'facebook'  => 'facebook.com',
            'instagram' => 'instagram.com',
            'linkedin'  => 'linkedin.com',
            'twitter'   => 'twitter.com',
        ];

        $found = [];

        foreach ($xpath->query('//a[@href]') as $node) {
            $href = $node->getAttribute('href');
            foreach ($map as $platform => $domain) {
                if (! isset($found[$platform]) && str_contains($href, $domain)) {
                    $found[$platform] = $href;
                }
            }
        }

        return $found;
    }

    private function extractSchemaTypes(string $html): array
    {
        preg_match_all('/"@type"\s*:\s*"([^"]+)"/', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    private function extractEmails(string $html): array
    {
        $text = html_entity_decode(strip_tags($html));
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $matches);

        // Filter out common false positives
        $blocked = ['example.com', 'sentry.io', 'wixpress.com', 'schema.org'];

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            fn (string $email) => ! collect($blocked)->contains(
                fn ($domain) => str_ends_with($email, $domain)
            )
        )));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function isThinContent(string $html): bool
    {
        return $this->countWords($html) < self::MIN_WORD_COUNT;
    }

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://' . ltrim($url, '/');
        }
        return rtrim($url, '/');
    }

    private function emptyParsedData(): array
    {
        return [
            'title'            => null,
            'meta_description' => null,
            'h1'               => null,
            'h2s'              => [],
            'h3s'              => [],
            'word_count'       => 0,
            'has_ssl'          => false,
            'has_contact_form' => false,
            'has_cta_button'   => false,
            'has_phone'        => false,
            'images_count'     => 0,
            'internal_links'   => 0,
            'external_links'   => 0,
            'social_links'     => [],
            'og_title'         => null,
            'og_description'   => null,
            'canonical_url'    => null,
            'robots_meta'      => null,
            'viewport_meta'    => null,
            'schema_types'     => [],
            'page_speed'       => null,
            'emails_found'     => [],
            'scraped_at'       => now()->toISOString(),
            'scraped_url'      => null,
        ];
    }
}
