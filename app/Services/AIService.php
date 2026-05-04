<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $fallbackModel;
    private int    $maxTokens;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey        = config('audit.openrouter.api_key');
        $this->model         = config('audit.openrouter.model',          'anthropic/claude-opus-4-6');
        $this->fallbackModel = config('audit.openrouter.fallback_model', 'openai/gpt-4o');
        $this->maxTokens     = config('audit.openrouter.max_tokens',     4096);
        $this->baseUrl       = config('audit.openrouter.base_url',       'https://openrouter.ai/api/v1');
    }

    // -----------------------------------------------------------------------
    // Public entry point
    // -----------------------------------------------------------------------

    /**
     * Run the full 15-point health audit for a business.
     *
     * Returns a validated audit array ready to be stored in reports.audit_data.
     * Throws \RuntimeException if both primary and fallback models fail.
     */
    public function audit(Business $business): array
    {
        $prompt = $this->buildUserPrompt($business);

        // Try primary model first
        $raw = $this->callOpenRouter($prompt, $this->model);

        // Fall back to secondary model
        if ($raw === null) {
            Log::warning("AIService: primary model [{$this->model}] failed, trying fallback [{$this->fallbackModel}]", [
                'business_id' => $business->id,
            ]);
            $raw = $this->callOpenRouter($prompt, $this->fallbackModel);
        }

        if ($raw === null) {
            throw new \RuntimeException(
                "AIService: Both [{$this->model}] and [{$this->fallbackModel}] failed for business #{$business->id}"
            );
        }

        $audit = $this->parseAndValidate($raw);
        $audit['_meta']['business_id'] = $business->id;

        return $audit;
    }

    // -----------------------------------------------------------------------
    // OpenRouter API call (OpenAI-compatible /chat/completions format)
    // -----------------------------------------------------------------------

    private function callOpenRouter(string $userPrompt, string $model): ?string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title'      => config('app.name', 'Business Audit System'),
                ])
                ->timeout(90)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'      => $model,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [
                        ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);

            if ($response->failed()) {
                Log::error("AIService: OpenRouter error [{$model}]", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $text = $response->json('choices.0.message.content');

            if (empty($text)) {
                Log::warning("AIService: empty content from [{$model}]", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            Log::debug("AIService: got response from [{$model}]", ['chars' => strlen($text)]);

            return $text;

        } catch (\Throwable $e) {
            Log::error("AIService: OpenRouter exception [{$model}]", ['error' => $e->getMessage()]);
            return null;
        }
    }

    // -----------------------------------------------------------------------
    // System prompt
    // -----------------------------------------------------------------------

    private function buildSystemPrompt(): string
    {
        return <<<'SYSTEM'
You are a senior digital marketing consultant specialising in SMB online presence audits.

Your task is to evaluate a local business's digital presence across exactly 15 health checks and return a structured JSON report.

## Output Format

Return ONLY a valid JSON object — no markdown, no explanation outside the JSON.

{
  "checks": [
    {
      "name": "website_speed",
      "label": "Website Speed",
      "score": 7,
      "status": "pass",
      "note": "Mobile performance score is 72/100 — acceptable but LCP could be improved."
    }
  ],
  "overall_score": 68,
  "summary": "2-3 sentence executive summary of the business's digital health.",
  "strengths": ["Clear and prominent phone number on every page"],
  "weaknesses": ["No email capture form or lead magnet on the website"],
  "opportunities": ["Adding Google Review schema markup could increase click-through rate"],
  "recommendations": [
    {
      "title": "Add a lead capture form",
      "description": "Implement a simple 'Book a free consultation' form above the fold.",
      "priority": "high"
    }
  ]
}

## The 15 Health Checks — Scoring Guide

Score each check from 0–10. Derive status from score:
- 7–10 → "pass"
- 4–6  → "warn"
- 0–3  → "fail"

Evaluate in this order and use these exact `name` keys:

1. **website_speed** — Based on PageSpeed mobile score. 80–100=9-10, 60–79=6-8, 40–59=3-5, <40=0-2.
2. **mobile_responsiveness** — Viewport meta tag, mobile PageSpeed score, responsive indicators. No viewport meta = 0–2.
3. **seo_optimization** — Title tag length (50-60 ideal), meta description, H1 presence, canonical URL, robots meta.
4. **google_reviews_rating** — Rating: 4.5+=10, 4.0-4.4=8, 3.5-3.9=6, 3.0-3.4=4, <3.0=1. No data=5.
5. **social_media_presence** — Number of active profiles found: 3+=9, 2=7, 1=4, 0=1.
6. **posting_frequency** — Infer from social link presence and content freshness signals.
7. **branding_consistency** — OG tags, consistent name, schema markup, visual cohesion.
8. **call_to_action_clarity** — has_cta_button, booking/contact clarity. Multiple CTAs=9-10, one=6-7, none=0-2.
9. **lead_capture_forms** — has_contact_form, email capture, newsletter signup.
10. **conversion_optimization** — Holistic score for CTA+form+phone+trust signals.
11. **content_quality** — word_count: 1000+=8-10, 500-999=6-7, 200-499=4-5, <200=1-3.
12. **paid_ads_presence** — Tracking pixels, UTM params, ad script indicators.
13. **email_marketing_usage** — Signup forms, newsletter links, ESP script indicators.
14. **trust_signals** — Google rating, reviews, schema types, SSL, testimonials.
15. **technical_performance** — SSL, canonical URL, robots meta, schema, valid viewport.

## Rules
- Be specific in notes — cite actual numbers from the data
- overall_score = average of all check scores × 10, rounded to integer
- Generate exactly 5 recommendations ordered by priority (high → medium → low)
- Generate 3–5 strengths, 3–5 weaknesses, 2–4 opportunities
SYSTEM;
    }

    // -----------------------------------------------------------------------
    // User prompt
    // -----------------------------------------------------------------------

    private function buildUserPrompt(Business $business): string
    {
        $scraped = $business->scraped_data ?? [];
        $speed   = $scraped['page_speed'] ?? null;

        $socialProfiles = implode(', ', array_keys(array_filter([
            'Facebook'  => $scraped['social_links']['facebook']  ?? null,
            'Instagram' => $scraped['social_links']['instagram'] ?? null,
            'LinkedIn'  => $scraped['social_links']['linkedin']  ?? null,
            'Twitter'   => $scraped['social_links']['twitter']   ?? null,
        ]))) ?: 'None found';

        $speedSummary = $speed
            ? sprintf(
                "Mobile: %d/100 (LCP: %sms, FCP: %sms) | Desktop: %d/100",
                $speed['mobile']['performance_score']  ?? 0,
                number_format($speed['mobile']['lcp_ms']  ?? 0, 0),
                number_format($speed['mobile']['fcp_ms']  ?? 0, 0),
                $speed['desktop']['performance_score'] ?? 0
            )
            : 'PageSpeed data unavailable';

        $schemaTypes = implode(', ', $scraped['schema_types'] ?? []) ?: 'None detected';

        return <<<PROMPT
Please audit the following business and return the JSON report as instructed.

## Business Information
- **Name:** {$business->name}
- **Type:** {$business->business_type}
- **Location:** {$business->location}
- **Website:** {$business->website}
- **Has Email Contact:** {$this->yesNo(!empty($business->email))}
- **Phone Listed:** {$this->yesNo(!empty($business->phone))}

## Google Presence
- **Rating:** {$this->val($business->google_rating, '/5')}
- **Review Count:** {$this->val($business->google_reviews_count)}

## Website Scraped Data
- **Page Title:** {$this->val($scraped['title'] ?? null)}
- **Meta Description:** {$this->val($scraped['meta_description'] ?? null)} ({$this->strLen($scraped['meta_description'] ?? null)} chars)
- **H1 Tag:** {$this->val($scraped['h1'] ?? null)}
- **H2 Tags:** {$this->listVal($scraped['h2s'] ?? [])}
- **Word Count:** {$this->val($scraped['word_count'] ?? null)} words
- **Image Count:** {$this->val($scraped['images_count'] ?? null)}
- **Internal Links:** {$this->val($scraped['internal_links'] ?? null)}
- **External Links:** {$this->val($scraped['external_links'] ?? null)}
- **SSL (HTTPS):** {$this->yesNo($scraped['has_ssl'] ?? false)}
- **Has Contact Form:** {$this->yesNo($scraped['has_contact_form'] ?? false)}
- **Has CTA Button:** {$this->yesNo($scraped['has_cta_button'] ?? false)}
- **Phone on Website:** {$this->yesNo($scraped['has_phone'] ?? false)}
- **Viewport Meta:** {$this->yesNo(!empty($scraped['viewport_meta']))}
- **Canonical URL:** {$this->yesNo(!empty($scraped['canonical_url']))}
- **Robots Meta:** {$this->val($scraped['robots_meta'] ?? null)}
- **OG Title:** {$this->yesNo(!empty($scraped['og_title']))}
- **OG Description:** {$this->yesNo(!empty($scraped['og_description']))}
- **Schema Types:** {$schemaTypes}

## PageSpeed Insights
- {$speedSummary}

## Social Media
- **Profiles Found:** {$socialProfiles}

Now produce the JSON audit report.
PROMPT;
    }

    // -----------------------------------------------------------------------
    // JSON parse + validate
    // -----------------------------------------------------------------------

    private function parseAndValidate(string $raw): array
    {
        $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $json = preg_replace('/\s*```$/m', '', $json);
        $json = trim($json);

        if (preg_match('/\{[\s\S]+\}/m', $json, $matches)) {
            $json = $matches[0];
        }

        $data = json_decode($json, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "AIService: JSON decode failed — " . json_last_error_msg() .
                "\nRaw (first 500 chars): " . substr($raw, 0, 500)
            );
        }

        $this->validateSchema($data);

        $data['checks'] = array_map(function (array $check) {
            $check['score']  = (int) round(max(0, min(10, $check['score'])));
            $check['status'] = $this->scoreToStatus($check['score']);
            return $check;
        }, $data['checks']);

        $data['overall_score'] = (int) round(
            collect($data['checks'])->avg('score') * 10
        );

        $data['_meta'] = [
            'generated_at' => now()->toISOString(),
            'model'        => $this->model,
        ];

        return $data;
    }

    private function validateSchema(array $data): void
    {
        foreach (['checks', 'overall_score', 'summary', 'strengths', 'weaknesses', 'recommendations'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new \RuntimeException("AIService: Missing required key [{$key}] in AI response.");
            }
        }

        if (! is_array($data['checks']) || count($data['checks']) !== 15) {
            throw new \RuntimeException(
                "AIService: Expected 15 health checks, got " . count($data['checks'] ?? [])
            );
        }

        $missing = array_diff(config('audit.health_checks'), array_column($data['checks'], 'name'));

        if (! empty($missing)) {
            throw new \RuntimeException(
                "AIService: Missing health check(s): " . implode(', ', $missing)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function scoreToStatus(int $score): string
    {
        return match (true) {
            $score >= 7 => 'pass',
            $score >= 4 => 'warn',
            default     => 'fail',
        };
    }

    private function yesNo(bool $val): string  { return $val ? 'Yes' : 'No'; }
    private function val(mixed $val, string $suffix = ''): string
    {
        return $val !== null && $val !== '' ? "{$val}{$suffix}" : 'Not available';
    }
    private function strLen(?string $val): int  { return $val ? mb_strlen($val) : 0; }
    private function listVal(array $items): string
    {
        return empty($items) ? 'None found' : implode(' | ', array_slice($items, 0, 5));
    }
}
