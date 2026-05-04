<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\EmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchWeeklyEmailsCommand extends Command
{
    protected $signature   = 'audit:dispatch-weekly-emails {--dry-run : Show what would be sent without dispatching}';
    protected $description = 'Dispatch pending outreach emails up to the configured weekly limit';

    public function handle(): int
    {
        $limit     = (int) config('audit.outreach.weekly_limit', 10);
        $sentCount = EmailLog::sentThisWeek()->count();
        $remaining = $limit - $sentCount;

        $this->line("Weekly limit : {$limit}");
        $this->line("Sent so far  : {$sentCount}");
        $this->line("Remaining    : {$remaining}");
        $this->newLine();

        if ($remaining <= 0) {
            $this->warn("Weekly limit already reached. No emails will be dispatched.");
            Log::info('DispatchWeeklyEmailsCommand: weekly limit already reached', [
                'sent'  => $sentCount,
                'limit' => $limit,
            ]);
            return self::SUCCESS;
        }

        // Fetch pending EmailLogs that have a recipient email, ordered oldest first
        $pending = EmailLog::where('status', 'pending')
            ->whereNotNull('recipient_email')
            ->whereHas('report', fn ($q) => $q->where('status', 'ready'))
            ->orderBy('created_at')
            ->limit($remaining)
            ->get();

        if ($pending->isEmpty()) {
            $this->info("No pending emails ready to send.");
            Log::info('DispatchWeeklyEmailsCommand: no pending emails found');
            return self::SUCCESS;
        }

        $this->info("Dispatching {$pending->count()} email(s)...");

        foreach ($pending as $emailLog) {
            if ($this->option('dry-run')) {
                $this->line("  [DRY RUN] Would send to: {$emailLog->recipient_email} — {$emailLog->subject}");
                continue;
            }

            SendEmailJob::dispatch($emailLog)->onQueue('email');

            $this->line("  Queued → {$emailLog->recipient_email}");

            Log::info('DispatchWeeklyEmailsCommand: dispatched', [
                'email_log_id' => $emailLog->id,
                'recipient'    => $emailLog->recipient_email,
            ]);
        }

        if (! $this->option('dry-run')) {
            $this->newLine();
            $this->info("Done. {$pending->count()} email(s) queued.");
        }

        return self::SUCCESS;
    }
}
