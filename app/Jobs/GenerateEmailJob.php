<?php

namespace App\Jobs;

use App\Models\JobLog;
use App\Models\Report;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [60, 120, 300];
    public int   $timeout = 90;

    public function __construct(
        private readonly Report $report
    ) {}

    public function handle(EmailService $emailService): void
    {
        $business = $this->report->business;

        if (empty($business->email)) {
            Log::info("GenerateEmailJob: skipping — no email address", [
                'business_id' => $business->id,
            ]);
            return;
        }

        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'business',
            entityId:   $business->id,
            payload:    ['report_id' => $this->report->id]
        );

        try {
            $emailLog = $emailService->generate($business, $this->report);

            $log->finish("EmailLog #{$emailLog->id} created. Subject: {$emailLog->subject}");

            Log::info("GenerateEmailJob completed — email is pending manual approval", [
                'business_id'  => $business->id,
                'email_log_id' => $emailLog->id,
            ]);

            // Email stays in 'pending' status — admin must review and send manually.

        } catch (Throwable $e) {
            $log->fail($e->getMessage());

            Log::error("GenerateEmailJob failed", [
                'business_id' => $business->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::critical("GenerateEmailJob exhausted retries", [
            'report_id' => $this->report->id,
            'error'     => $e->getMessage(),
        ]);
    }
}
