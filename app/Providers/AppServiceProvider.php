<?php

namespace App\Providers;

use App\Services\AIService;
use App\Services\BusinessFetcherService;
use App\Services\EmailService;
use App\Services\ReportService;
use App\Services\ScraperService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * All audit services are registered as singletons so their constructor
     * (which reads config) only runs once per request/job.
     */
    public function register(): void
    {
        $this->app->singleton(BusinessFetcherService::class);
        $this->app->singleton(ScraperService::class);
        $this->app->singleton(AIService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(EmailService::class);
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Enforce HTTPS scheme in production for generated URLs
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
