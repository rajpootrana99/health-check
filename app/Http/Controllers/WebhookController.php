<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook events from Resend.
     *
     * Resend sends POST requests with JSON payloads for events:
     *   email.sent, email.delivered, email.opened, email.clicked,
     *   email.bounced, email.complained
     *
     * Docs: https://resend.com/docs/dashboard/webhooks/event-types
     */
    public function resend(Request $request): Response
    {
        // ------------------------------------------------------------------
        // Signature verification
        // ------------------------------------------------------------------
        if (! $this->verifyResendSignature($request)) {
            Log::warning('WebhookController: invalid Resend signature');
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();
        $type    = $payload['type'] ?? null;
        $data    = $payload['data'] ?? [];

        Log::info("WebhookController: Resend event [{$type}]", [
            'message_id' => $data['email_id'] ?? null,
        ]);

        match ($type) {
            'email.opened'    => $this->handleOpened($data),
            'email.clicked'   => $this->handleClicked($data),
            'email.bounced'   => $this->handleBounced($data),
            'email.complained'=> $this->handleComplained($data),
            default           => null,
        };

        return response('OK', 200);
    }

    // -----------------------------------------------------------------------
    // Event handlers
    // -----------------------------------------------------------------------

    private function handleOpened(array $data): void
    {
        $log = $this->findByProviderId($data['email_id'] ?? null);

        if (! $log || $log->opened) {
            return;
        }

        $log->update([
            'opened'    => true,
            'opened_at' => now(),
        ]);

        Log::info("WebhookController: email opened", ['email_log_id' => $log->id]);
    }

    private function handleClicked(array $data): void
    {
        $log = $this->findByProviderId($data['email_id'] ?? null);

        if (! $log) {
            return;
        }

        $update = ['clicked' => true, 'clicked_at' => now()];

        // Also mark opened if not already
        if (! $log->opened) {
            $update['opened']    = true;
            $update['opened_at'] = now();
        }

        $log->update($update);

        Log::info("WebhookController: email clicked", ['email_log_id' => $log->id]);
    }

    private function handleBounced(array $data): void
    {
        $log = $this->findByProviderId($data['email_id'] ?? null);

        if (! $log) {
            return;
        }

        $log->update([
            'status'         => 'bounced',
            'failure_reason' => 'Bounced: ' . ($data['bounce']['message'] ?? 'unknown reason'),
        ]);

        Log::warning("WebhookController: email bounced", [
            'email_log_id' => $log->id,
            'recipient'    => $log->recipient_email,
        ]);
    }

    private function handleComplained(array $data): void
    {
        // Spam complaint — mark bounced and log at critical level
        $log = $this->findByProviderId($data['email_id'] ?? null);

        if ($log) {
            $log->update([
                'status'         => 'bounced',
                'failure_reason' => 'Spam complaint received.',
            ]);
        }

        Log::critical("WebhookController: spam complaint received", [
            'email_id'  => $data['email_id'] ?? null,
            'recipient' => $data['to'][0] ?? null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function findByProviderId(?string $providerId): ?EmailLog
    {
        if (! $providerId) {
            return null;
        }

        return EmailLog::where('provider_message_id', $providerId)->first();
    }

    /**
     * Verify the svix-signature header sent by Resend.
     *
     * Resend uses the Svix webhook library for signing.
     * Full verification requires the `svix/svix` PHP package.
     * Here we do a lightweight HMAC check against RESEND_WEBHOOK_SECRET.
     *
     * Set RESEND_WEBHOOK_SECRET in .env from your Resend dashboard.
     */
    private function verifyResendSignature(Request $request): bool
    {
        $secret = config('services.resend.webhook_secret');

        // If no secret configured, skip verification (development mode)
        if (empty($secret)) {
            return true;
        }

        $svixId        = $request->header('svix-id');
        $svixTimestamp = $request->header('svix-timestamp');
        $svixSignature = $request->header('svix-signature');

        if (! $svixId || ! $svixTimestamp || ! $svixSignature) {
            return false;
        }

        $signedContent = "{$svixId}.{$svixTimestamp}." . $request->getContent();

        $secretBytes = base64_decode(substr($secret, 6)); // strip "whsec_" prefix
        $computed    = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        // svix-signature may contain multiple space-separated values
        foreach (explode(' ', $svixSignature) as $sig) {
            $sigValue = substr($sig, 3); // strip "v1,"
            if (hash_equals($computed, $sigValue)) {
                return true;
            }
        }

        return false;
    }
}
