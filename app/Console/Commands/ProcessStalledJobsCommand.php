<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportJob;
use App\Jobs\ScrapeBusinessJob;
use App\Models\Business;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStalledJobsCommand extends Command
{
    protected $signature   = 'audit:process-stalled {--stall-minutes=120 : Minutes before a job is considered stalled}';
    protected $description = 'Detect and re-dispatch businesses stuck in intermediate pipeline states';

    public function handle(): int
    {
        $stallMinutes = (int) $this->option('stall-minutes');
        $threshold    = now()->subMinutes($stallMinutes);
        $requeued     = 0;

        $this->line("Checking for businesses stalled for > {$stallMinutes} minutes...");
        $this->newLine();

        // ------------------------------------------------------------------
        // Businesses stuck in 'scraping' — re-dispatch ScrapeBusinessJob
        // ------------------------------------------------------------------
        $stalledScraping = Business::where('status', 'scraping')
            ->whereNotNull('website')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($stalledScraping as $business) {
            $this->warn("  Re-scraping: [{$business->id}] {$business->name}");

            $business->markAs('fetched', 'Re-queued by stall detector');
            ScrapeBusinessJob::dispatch($business)->onQueue('scraping');

            Log::warning('ProcessStalledJobsCommand: re-dispatched ScrapeBusinessJob', [
                'business_id' => $business->id,
                'stalled_at'  => $business->updated_at,
            ]);

            $requeued++;
        }

        // ------------------------------------------------------------------
        // Businesses stuck in 'auditing' — re-dispatch GenerateReportJob
        // ------------------------------------------------------------------
        $stalledAuditing = Business::where('status', 'auditing')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($stalledAuditing as $business) {
            $this->warn("  Re-auditing: [{$business->id}] {$business->name}");

            // Delete the stuck pending report if one exists
            $business->reports()
                ->where('status', 'generating')
                ->delete();

            $business->markAs('scraped', 'Re-queued by stall detector');
            GenerateReportJob::dispatch($business)->onQueue('ai');

            Log::warning('ProcessStalledJobsCommand: re-dispatched GenerateReportJob', [
                'business_id' => $business->id,
                'stalled_at'  => $business->updated_at,
            ]);

            $requeued++;
        }

        // ------------------------------------------------------------------
        // Summary
        // ------------------------------------------------------------------
        if ($requeued === 0) {
            $this->info("No stalled jobs found.");
        } else {
            $this->newLine();
            $this->info("Re-queued {$requeued} stalled job(s).");
        }

        Log::info('ProcessStalledJobsCommand: completed', [
            'stall_minutes' => $stallMinutes,
            'requeued'      => $requeued,
        ]);

        return self::SUCCESS;
    }
}
