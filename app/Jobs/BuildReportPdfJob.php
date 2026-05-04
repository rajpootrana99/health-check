<?php

namespace App\Jobs;

use App\Models\JobLog;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BuildReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [30, 60, 120];
    public int   $timeout = 60;

    public function __construct(
        private readonly Report $report
    ) {}

    public function handle(ReportService $service): void
    {
        // Skip if PDF already built (idempotency)
        if ($this->report->pdf_path !== null) {
            $this->dispatchEmail();
            return;
        }

        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'business',
            entityId:   $this->report->business_id,
            payload:    ['report_id' => $this->report->id]
        );

        try {
            $path = $service->generatePdf($this->report);

            $log->finish("PDF saved: {$path}");

            Log::info("BuildReportPdfJob completed", [
                'report_id'   => $this->report->id,
                'business_id' => $this->report->business_id,
                'pdf_path'    => $path,
            ]);

            $this->dispatchEmail();

        } catch (Throwable $e) {
            $log->fail($e->getMessage());

            $this->report->update(['status' => 'failed', 'generation_error' => $e->getMessage()]);

            Log::error("BuildReportPdfJob failed", [
                'report_id' => $this->report->id,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::critical("BuildReportPdfJob exhausted retries", [
            'report_id' => $this->report->id,
            'error'     => $e->getMessage(),
        ]);
    }

    // -----------------------------------------------------------------------

    private function dispatchEmail(): void
    {
        // GenerateEmailJob will check for email address itself and skip if absent
        GenerateEmailJob::dispatch($this->report->fresh())
            ->onQueue('default');
    }
}
