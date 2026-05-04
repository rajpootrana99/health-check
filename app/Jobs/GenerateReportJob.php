<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\JobLog;
use App\Models\Report;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * AI APIs can be slow under load — allow 3 attempts.
     */
    public int $tries = 3;

    /**
     * Back off more generously for AI rate limits.
     * Retries at 60s, 120s, 300s.
     */
    public array $backoff = [60, 120, 300];

    /**
     * Claude can take up to 60s for long responses.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly Business $business
    ) {}

    public function handle(AIService $ai): void
    {
        // Skip if already audited downstream
        if (in_array($this->business->status, ['auditing', 'audited', 'emailed'])) {
            return;
        }

        $this->business->markAs('auditing');

        // Create a pending report record immediately so the admin panel
        // can show "audit in progress" without polling
        $report = Report::create([
            'business_id' => $this->business->id,
            'status'      => 'generating',
        ]);

        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'business',
            entityId:   $this->business->id,
            payload:    [
                'business_name' => $this->business->name,
                'report_id'     => $report->id,
            ]
        );

        try {
            $auditData = $ai->audit($this->business);

            $report->update([
                'audit_data'     => $auditData,
                'overall_score'  => $auditData['overall_score'],
                'summary'        => $auditData['summary'],
                'status'         => 'ready',
                'ai_model_used'  => $auditData['_meta']['model']        ?? null,
                'generated_at'   => $auditData['_meta']['generated_at'] ?? now(),
            ]);

            $this->business->markAs('audited');

            $log->finish("Overall score: {$auditData['overall_score']}/100");

            Log::info("GenerateReportJob completed", [
                'business_id'   => $this->business->id,
                'report_id'     => $report->id,
                'overall_score' => $auditData['overall_score'],
            ]);

            // Chain: generate PDF + queue email
            // (ReportService handles both — Step 6)
            \App\Jobs\BuildReportPdfJob::dispatch($report->fresh())
                ->onQueue('default');

        } catch (Throwable $e) {
            $report->update([
                'status'           => 'failed',
                'generation_error' => $e->getMessage(),
            ]);

            $this->business->markAs('failed', $e->getMessage());
            $log->fail($e->getMessage());

            Log::error("GenerateReportJob failed", [
                'business_id' => $this->business->id,
                'report_id'   => $report->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $this->business->markAs('failed', 'AI audit exhausted retries: ' . $e->getMessage());

        Log::critical("GenerateReportJob exhausted retries", [
            'business_id' => $this->business->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
