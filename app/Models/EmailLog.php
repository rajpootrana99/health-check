<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'report_id',
        'subject',
        'body_html',
        'body_plain',
        'recipient_email',
        'recipient_name',
        'status',
        'failure_reason',
        'attempts',
        'opened',
        'clicked',
        'opened_at',
        'clicked_at',
        'provider_message_id',
        'week_number',
        'week_year',
        'sent_at',
    ];

    protected $casts = [
        'opened'     => 'boolean',
        'clicked'    => 'boolean',
        'opened_at'  => 'datetime',
        'clicked_at' => 'datetime',
        'sent_at'    => 'datetime',
        'attempts'   => 'integer',
        'week_number'=> 'integer',
        'week_year'  => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSentThisWeek($query)
    {
        $now = now();
        return $query
            ->where('status', 'sent')
            ->where('week_year', $now->year)
            ->where('week_number', $now->weekOfYear);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function markSent(string $providerMessageId): void
    {
        $this->update([
            'status'              => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at'             => now(),
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->increment('attempts');
        $this->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
        ]);
    }
}
