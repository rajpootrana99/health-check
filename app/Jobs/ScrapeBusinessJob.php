<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\JobLog;
use App\Services\ScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeBusinessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times — network issues are transient.
     */
    public int $tries = 3;

    /**
     * Exponential backoff: 30s, 60s, 120s between retries.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Allow up to 60 seconds for fetch + DOM parse + PageSpeed API.
     */
    public int $timeout = 90;

    public function __construct(
        private readonly Business $business
    ) {}

    public function handle(ScraperService $scraper): void
    {
        // Skip if already scraped (idempotent re-dispatch safety)
        if (in_array($this->business->status, ['scraped', 'auditing', 'audited', 'emailed'])) {
            return;
        }

        $this->business->markAs('scraping');

        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'business',
            entityId:   $this->business->id,
            payload:    [
                'website' => $this->business->website,
                'name'    => $this->business->name,
            ]
        );

        try {
            $scrapedData = $scraper->scrape($this->business);

            $log->finish("Scraped {$scrapedData['word_count']} words. " .
                "PageSpeed: " . ($scrapedData['page_speed']['mobile']['performance_score'] ?? 'n/a'));

            Log::info("ScrapeBusinessJob completed", [
                'business_id' => $this->business->id,
                'word_count'  => $scrapedData['word_count'],
            ]);

            // Chain: dispatch AI audit job now that scraping is done
            GenerateReportJob::dispatch($this->business->fresh())
                ->onQueue('ai');

        } catch (Throwable $e) {
            $log->fail($e->getMessage());
            $this->business->markAs('failed', $e->getMessage());

            Log::error("ScrapeBusinessJob failed", [
                'business_id' => $this->business->id,
                'url'         => $this->business->website,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * All retries exhausted.
     */
    public function failed(Throwable $e): void
    {
        $this->business->markAs('failed', 'Scraping exhausted retries: ' . $e->getMessage());

        Log::critical("ScrapeBusinessJob exhausted retries", [
            'business_id' => $this->business->id,
            'url'         => $this->business->website,
            'error'       => $e->getMessage(),
        ]);
    }
}
