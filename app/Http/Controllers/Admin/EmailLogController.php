<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\EmailLog;

class EmailLogController extends Controller
{
    public function index()
    {
        $emailLogs = EmailLog::with(['business', 'report'])
            ->orderByDesc('created_at')
            ->paginate(25);

        // Stats for this week
        $weekStats = [
            'sent'    => EmailLog::sentThisWeek()->count(),
            'limit'   => (int) config('audit.outreach.weekly_limit', 10),
            'opened'  => EmailLog::sentThisWeek()->where('opened', true)->count(),
            'clicked' => EmailLog::sentThisWeek()->where('clicked', true)->count(),
        ];

        return view('admin.email-logs.index', compact('emailLogs', 'weekStats'));
    }

    /**
     * Dispatch SendEmailJob for a single pending email (manual approval action).
     */
    public function send(EmailLog $emailLog)
    {
        abort_if(
            ! in_array($emailLog->status, ['pending', 'failed']),
            422,
            'This email has already been sent or is currently queued.'
        );

        SendEmailJob::dispatch($emailLog)->onQueue('email');

        $emailLog->update(['status' => 'queued']);

        return back()->with('success', "Email to {$emailLog->recipient_email} has been queued for sending.");
    }

    /**
     * Return the composed email body as an HTML snippet for the preview modal.
     */
    public function preview(EmailLog $emailLog)
    {
        return response($emailLog->body_html ?? '<p><em>No body available.</em></p>', 200)
            ->header('Content-Type', 'text/html');
    }
}
