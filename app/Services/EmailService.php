<?php

namespace App\Services;

use App\Models\Business;
use App\Models\EmailLog;
use App\Models\Report;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
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
        $this->maxTokens     = (int) config('audit.openrouter.max_tokens', 2048);
        $this->baseUrl       = config('audit.openrouter.base_url',        'https://openrouter.ai/api/v1');
    }

    // -----------------------------------------------------------------------
    // Public entry point
    // -----------------------------------------------------------------------

    /**
     * Generate a personalised outreach email for the business and persist it
     * as an EmailLog record ready for the send queue.
     *
     * Returns the created EmailLog.
     */
    public function generate(Business $business, Report $report): EmailLog
    {
        $system = $this->buildSystemPrompt();
        $user   = $this->buildUserPrompt($business, $report);

        // Try primary model first, fall back to secondary
        $raw = $this->callOpenRouter($system, $user, $this->model);

        if ($raw === null) {
            Log::warning("EmailService: primary model [{$this->model}] failed, trying fallback [{$this->fallbackModel}]", [
                'business_id' => $business->id,
            ]);
            $raw = $this->callOpenRouter($system, $user, $this->fallbackModel);
        }

        if ($raw === null) {
            throw new \RuntimeException(
                "EmailService: Both [{$this->model}] and [{$this->fallbackModel}] failed for business #{$business->id}"
            );
        }

        ['subject' => $subject, 'html' => $html, 'plain' => $plain] =
            $this->parseResponse($raw, $business);

        $now = now();

        $emailLog = EmailLog::create([
            'business_id'     => $business->id,
            'report_id'       => $report->id,
            'subject'         => $subject,
            'body_html'       => $html,
            'body_plain'      => $plain,
            'recipient_email' => $business->email,
            'recipient_name'  => $business->name,
            'status'          => 'pending',
            'week_number'     => (int) $now->format('W'),
            'week_year'       => (int) $now->year,
        ]);

        Log::info("EmailService: email generated", [
            'business_id'  => $business->id,
            'email_log_id' => $emailLog->id,
            'subject'      => $subject,
        ]);

        return $emailLog;
    }

    // -----------------------------------------------------------------------
    // Claude call
    // -----------------------------------------------------------------------

    private function callOpenRouter(string $system, string $user, string $model): ?string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title'      => config('app.name', 'Business Audit System'),
                ])
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'      => $model,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                ]);

            if ($response->failed()) {
                Log::error("EmailService: OpenRouter error [{$model}]", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $text = $response->json('choices.0.message.content');

            if (empty($text)) {
                Log::warning("EmailService: empty content from [{$model}]", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $text;

        } catch (\Throwable $e) {
            Log::error("EmailService: OpenRouter exception [{$model}]", ['error' => $e->getMessage()]);
            return null;
        }
    }

    // -----------------------------------------------------------------------
    // System prompt
    // -----------------------------------------------------------------------

    private function buildSystemPrompt(): string
    {
        return <<<'SYSTEM'
You are an expert B2B copywriter who specialises in cold outreach for digital marketing agencies.

Your task is to write a single cold outreach email to a local business owner.

## Tone
- Friendly, direct, and conversational — NOT corporate or pushy
- Lead with empathy, not a sales pitch
- Sound like a human who genuinely noticed problems with their business
- Short paragraphs, 2–3 sentences max each
- No clichés ("I hope this email finds you well", "synergy", "leverage")

## Output Format
Return ONLY a JSON object with this exact shape — no markdown, no preamble:

```json
{
  "subject": "Your email subject line here",
  "html": "<p>Full HTML body of the email...</p>",
  "plain": "Full plain-text version of the email..."
}
```

## Rules for the Subject Line
- Max 60 characters
- Must feel personal, not generic
- Should hint at a specific finding (e.g. score, missing feature, opportunity)
- No emojis

## Rules for the HTML Body
- Use only basic HTML: <p>, <strong>, <a>, <br>, <ul>, <li>
- No inline styles (the email template handles styling)
- Include the business name naturally in the first sentence
- Mention 2–3 specific findings from the audit data provided
- One clear CTA: a link to book a call (use placeholder URL: https://calendly.com/your-link)
- Sign off with a real-sounding name: "Alex" from "Digital Audit Co."
- Keep total length under 250 words

## Rules for the Plain Text Body
- Same content as HTML, no tags
- Use --- as a section separator before the CTA
SYSTEM;
    }

    // -----------------------------------------------------------------------
    // User prompt
    // -----------------------------------------------------------------------

    private function buildUserPrompt(Business $business, Report $report): string
    {
        $audit    = $report->audit_data ?? [];
        $checks   = collect($audit['checks'] ?? []);

        // Pull out the 3 worst-scoring checks to highlight as pain points
        $worst = $checks->sortBy('score')->take(3)->values();

        // Pull out the top 2 strengths to acknowledge (builds credibility)
        $best = $checks->sortByDesc('score')->take(2)->values();

        $worstText = $worst->map(fn ($c) =>
            "- " . ucwords(str_replace('_', ' ', $c['name'])) .
            ": score {$c['score']}/10 — {$c['note']}"
        )->implode("\n");

        $bestText = $best->map(fn ($c) =>
            "- " . ucwords(str_replace('_', ' ', $c['name'])) .
            ": score {$c['score']}/10"
        )->implode("\n");

        $recommendations = collect($audit['recommendations'] ?? [])
            ->take(2)
            ->map(fn ($r) => "- {$r['title']}: {$r['description']}")
            ->implode("\n");

        $score        = $report->overall_score ?? 'unknown';
        $website      = $business->website ?? 'their website';
        $summary      = $audit['summary'] ?? 'Not available';
        $rating       = $this->val($business->google_rating, '/5');
        $reviewsCount = $this->val($business->google_reviews_count);

        return <<<PROMPT
Write a personalised cold outreach email for the following business:

## Business Details
- Name: {$business->name}
- Type: {$business->business_type}
- Location: {$business->location}
- Website: {$website}
- Google Rating: {$rating}
- Google Reviews: {$reviewsCount}

## Audit Summary
- Overall Health Score: {$score}/100
- Summary: {$summary}

## Top Pain Points (use 2–3 of these in the email)
{$worstText}

## What They're Doing Well (acknowledge 1 of these briefly)
{$bestText}

## Top Recommendations (reference 1 naturally)
{$recommendations}

Write the email now. Make it feel like it was written specifically for {$business->name},
not copy-pasted. The owner's name is unknown — address them as "Hi there," or "Hi [First Name]," placeholder.
PROMPT;
    }

    // -----------------------------------------------------------------------
    // Parse AI response
    // -----------------------------------------------------------------------

    /**
     * Extract subject, html, and plain from the raw Claude JSON response.
     * Falls back to a safe default if parsing fails.
     */
    private function parseResponse(string $raw, Business $business): array
    {
        // Strip markdown fences
        $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $json = preg_replace('/\s*```$/m', '', $json);

        if (preg_match('/\{[\s\S]+\}/m', $json, $matches)) {
            $json = $matches[0];
        }

        $data = json_decode(trim($json), associative: true);

        if (json_last_error() === JSON_ERROR_NONE
            && ! empty($data['subject'])
            && ! empty($data['html'])
        ) {
            return [
                'subject' => $data['subject'],
                'html'    => $data['html'],
                'plain'   => $data['plain'] ?? strip_tags($data['html']),
            ];
        }

        Log::warning("EmailService: JSON parse failed, using fallback template", [
            'business_id' => $business->id,
            'raw_snippet' => substr($raw, 0, 300),
        ]);

        return $this->fallbackEmail($business);
    }

    // -----------------------------------------------------------------------
    // Fallback email (if AI fails to return valid JSON)
    // -----------------------------------------------------------------------

    private function fallbackEmail(Business $business): array
    {
        $subject = "Quick question about {$business->name}'s online presence";

        $html = <<<HTML
<p>Hi there,</p>
<p>I came across <strong>{$business->name}</strong> while researching {$business->business_type}
businesses in {$business->location} and ran a quick digital audit of your online presence.</p>
<p>I noticed a few areas that could be improved — particularly around SEO and lead capture —
that are likely costing you new customers every month.</p>
<p>I'd love to share the full report with you and walk through a few quick wins that could
make a real difference. Would you be open to a 15-minute call this week?</p>
<p><a href="https://calendly.com/your-link">Book a free 15-minute call here</a></p>
<p>Best,<br>Alex<br>Digital Audit Co.</p>
HTML;

        return [
            'subject' => $subject,
            'html'    => $html,
            'plain'   => strip_tags(str_replace(['<br>', '<br/>'], "\n", $html)),
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function val(mixed $val, string $suffix = ''): string
    {
        return $val !== null && $val !== '' ? "{$val}{$suffix}" : 'N/A';
    }
}
