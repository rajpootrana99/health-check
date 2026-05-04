<?php

namespace App\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AuditOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EmailLog $emailLog
    ) {}

    // -----------------------------------------------------------------------

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailLog->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view:      'emails.outreach',
            text:      'emails.outreach-plain',
            with: [
                'emailLog' => $this->emailLog,
                'business' => $this->emailLog->business,
            ],
        );
    }

    /**
     * Attach the PDF report if it exists on disk.
     *
     * @return array<Attachment>
     */
    public function attachments(): array
    {
        $report = $this->emailLog->report;

        if (! $report || ! $report->pdf_path) {
            return [];
        }

        if (! Storage::exists($report->pdf_path)) {
            return [];
        }

        $filename = basename($report->pdf_path);

        return [
            Attachment::fromStorageDisk('local', $report->pdf_path)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
