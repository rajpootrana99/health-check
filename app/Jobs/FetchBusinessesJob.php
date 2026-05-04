<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\JobLog;
use App\Services\BusinessFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchBusinessesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     * Google Places rate-limits are generous; 3 retries is sufficient.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying after a failure.
     */
    public int $backoff = 30;

    /**
     * Maximum seconds the job may run before being killed.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly Campaign $campaign
    ) {}

    public function handle(BusinessFetcherService $service): void
    {
        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'campaign',
            entityId:   $this->campaign->id,
            payload:    [
                'business_type' => $this->campaign->business_type,
                'location'      => $this->campaign->location,
            ]
        );

        try {
            $count = $service->fetch($this->campaign);

            $log->finish("Stored {$count} businesses.");

            Log::info("FetchBusinessesJob completed", [
                'campaign_id' => $this->campaign->id,
                'stored'      => $count,
            ]);

            // Dispatch scraping jobs for each fetched business that has a website
            $this->campaign
                ->businesses()
                ->where('status', 'fetched')
                ->whereNotNull('website')
                ->cursor()
                ->each(fn ($business) => ScrapeBusinessJob::dispatch($business)
                    ->onQueue('scraping'));

        } catch (Throwable $e) {
            $log->fail($e->getMessage());
            $this->campaign->markAs('failed');

            Log::error("FetchBusinessesJob failed", [
                'campaign_id' => $this->campaign->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e; // re-throw so Laravel marks the job as failed
        }
    }

    /**
     * Handle a job that has exceeded its maximum attempts.
     */
    public function failed(Throwable $e): void
    {
        $this->campaign->markAs('failed');

        Log::critical("FetchBusinessesJob exhausted retries", [
            'campaign_id' => $this->campaign->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
