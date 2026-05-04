<?php

namespace App\Jobs;

use App\Mail\AuditOutreachMail;
use App\Models\EmailLog;
use App\Models\JobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [60, 120, 300];
    public int   $timeout = 60;

    public function __construct(
        private readonly EmailLog $emailLog
    ) {}

    // -----------------------------------------------------------------------

    public function handle(): void
    {
        // ------------------------------------------------------------------
        // Guard: skip if already sent or has no recipient
        // ------------------------------------------------------------------
        if ($this->emailLog->status === 'sent') {
            return;
        }

        if (empty($this->emailLog->recipient_email)) {
            Log::warning("SendEmailJob: no recipient email, aborting", [
                'email_log_id' => $this->emailLog->id,
            ]);
            $this->emailLog->markFailed('No recipient email address.');
            return;
        }

        // ------------------------------------------------------------------
        // Weekly limit check
        // ------------------------------------------------------------------
        $weeklyLimit = (int) config('audit.outreach.weekly_limit', 10);
        $sentThisWeek = EmailLog::sentThisWeek()->count();

        if ($sentThisWeek >= $weeklyLimit) {
            Log::info("SendEmailJob: weekly limit ({$weeklyLimit}) reached — re-queuing for next week", [
                'email_log_id' => $this->emailLog->id,
                'sent_this_week' => $sentThisWeek,
            ]);

            // Release back onto the queue after 7 days
            $this->release(now()->addDays(7)->diffInSeconds(now()));
            return;
        }

        // ------------------------------------------------------------------
        // Mark as queued so the UI reflects current state
        // ------------------------------------------------------------------
        $this->emailLog->update(['status' => 'queued', 'attempts' => $this->emailLog->attempts + 1]);

        $log = JobLog::start(
            jobClass:   static::class,
            entityType: 'business',
            entityId:   $this->emailLog->business_id,
            payload:    [
                'email_log_id' => $this->emailLog->id,
                'recipient'    => $this->emailLog->recipient_email,
            ]
        );

        try {
            // ------------------------------------------------------------------
            // Send
            // ------------------------------------------------------------------
            $sentMessage = Mail::to(
                    $this->emailLog->recipient_email,
                    $this->emailLog->recipient_name
                )
                ->send(new AuditOutreachMail($this->emailLog));

            // Extract provider message ID (Resend returns it in the message ID header)
            $messageId = $this->extractMessageId($sentMessage);

            $this->emailLog->markSent($messageId ?? '');

            // Update business status
            $this->emailLog->business?->markAs('emailed');

            // Refresh campaign counts
            $this->emailLog->business?->campaign?->refreshCounts();

            $log->finish("Sent to {$this->emailLog->recipient_email}. Message ID: {$messageId}");

            Log::info("SendEmailJob: email sent", [
                'email_log_id' => $this->emailLog->id,
                'recipient'    => $this->emailLog->recipient_email,
                'message_id'   => $messageId,
            ]);

        } catch (Throwable $e) {
            $log->fail($e->getMessage());
            $this->emailLog->markFailed($e->getMessage());

            Log::error("SendEmailJob failed", [
                'email_log_id' => $this->emailLog->id,
                'recipient'    => $this->emailLog->recipient_email,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -----------------------------------------------------------------------

    public function failed(Throwable $e): void
    {
        $this->emailLog->update([
            'status'         => 'failed',
            'failure_reason' => 'Exhausted retries: ' . $e->getMessage(),
        ]);

        Log::critical("SendEmailJob exhausted retries", [
            'email_log_id' => $this->emailLog->id,
            'error'        => $e->getMessage(),
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Extract the Message-ID header from the sent message object.
     * Laravel's Mail::send() returns a SentMessage (Symfony) instance.
     */
    private function extractMessageId(mixed $sentMessage): ?string
    {
        if ($sentMessage === null) {
            return null;
        }

        // Symfony SentMessage
        if (method_exists($sentMessage, 'getMessageId')) {
            return $sentMessage->getMessageId();
        }

        // Resend SDK response stored in debug header
        if (method_exists($sentMessage, 'getOriginalMessage')) {
            $orig = $sentMessage->getOriginalMessage();
            if (method_exists($orig, 'getHeaders')) {
                $headers = $orig->getHeaders();
                if (method_exists($headers, 'get')) {
                    return $headers->get('X-Resend-Id')?->getBodyAsString();
                }
            }
        }

        return null;
    }
}
