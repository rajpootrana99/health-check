<?php

/**
 * Audit system configuration.
 *
 * All sensitive values are read from environment variables.
 * Never hard-code API keys in this file.
 */

return [

    // -----------------------------------------------------------------------
    // Google Places
    // -----------------------------------------------------------------------
    'google_places' => [
        'api_key'  => env('GOOGLE_PLACES_API_KEY'),
        'base_url' => env('GOOGLE_PLACES_BASE_URL', 'https://maps.googleapis.com/maps/api/place'),
    ],

    // -----------------------------------------------------------------------
    // OpenRouter (unified AI gateway — used by AIService & EmailService)
    // OpenRouter is OpenAI-API-compatible; models are specified as
    // "provider/model-name" (e.g. "anthropic/claude-opus-4-6", "openai/gpt-4o")
    // -----------------------------------------------------------------------
    'openrouter' => [
        'api_key'        => env('OPENROUTER_API_KEY'),
        'model'          => env('OPENROUTER_MODEL',          'anthropic/claude-opus-4-6'),
        'fallback_model' => env('OPENROUTER_FALLBACK_MODEL', 'openai/gpt-4o'),
        'max_tokens'     => (int) env('OPENROUTER_MAX_TOKENS', 4096),
        'base_url'       => env('OPENROUTER_BASE_URL',       'https://openrouter.ai/api/v1'),
    ],

    // -----------------------------------------------------------------------
    // Claude AI (Anthropic) — kept for reference, not called directly
    // -----------------------------------------------------------------------
    'anthropic' => [
        'api_key'    => env('ANTHROPIC_API_KEY'),
        'model'      => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
        'base_url'   => 'https://api.anthropic.com/v1',
    ],

    // -----------------------------------------------------------------------
    // OpenAI (fallback) — kept for reference, not called directly
    // -----------------------------------------------------------------------
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    // -----------------------------------------------------------------------
    // Scraper microservice (Node/Puppeteer)
    // -----------------------------------------------------------------------
    'scraper' => [
        'url'    => env('SCRAPER_SERVICE_URL', 'http://localhost:3001'),
        'secret' => env('SCRAPER_SERVICE_SECRET'),
        'timeout'=> 30, // seconds
    ],

    // -----------------------------------------------------------------------
    // PageSpeed Insights
    // -----------------------------------------------------------------------
    'pagespeed' => [
        'api_key'  => env('PAGESPEED_API_KEY'),
        'base_url' => env('PAGESPEED_BASE_URL', 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'),
    ],

    // -----------------------------------------------------------------------
    // PDF generation
    // -----------------------------------------------------------------------
    'pdf' => [
        'storage_path' => env('PDF_STORAGE_PATH', 'pdfs/reports'),
        'disk'         => env('FILESYSTEM_DISK', 'local'),
    ],

    // -----------------------------------------------------------------------
    // Email outreach scheduling
    // -----------------------------------------------------------------------
    'outreach' => [
        'weekly_limit'   => (int) env('WEEKLY_EMAIL_LIMIT', 10),
        'send_day'       => env('EMAIL_SEND_DAY', 'monday'),
        'send_time'      => env('EMAIL_SEND_TIME', '09:00'),
    ],

    // -----------------------------------------------------------------------
    // Health check definitions (15 items)
    // These labels are passed into the AI prompt and matched in the JSON response.
    // -----------------------------------------------------------------------
    'health_checks' => [
        'website_speed',
        'mobile_responsiveness',
        'seo_optimization',
        'google_reviews_rating',
        'social_media_presence',
        'posting_frequency',
        'branding_consistency',
        'call_to_action_clarity',
        'lead_capture_forms',
        'conversion_optimization',
        'content_quality',
        'paid_ads_presence',
        'email_marketing_usage',
        'trust_signals',
        'technical_performance',
    ],

];
