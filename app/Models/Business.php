<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'name',
        'business_type',
        'location',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'google_place_id',
        'google_rating',
        'google_reviews_count',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'twitter_url',
        'scraped_data',
        'status',
        'status_message',
    ];

    protected $casts = [
        'scraped_data'         => 'array',
        'latitude'             => 'float',
        'longitude'            => 'float',
        'google_rating'        => 'float',
        'google_reviews_count' => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function latestReport(): HasOne
    {
        return $this->hasOne(Report::class)->latestOfMany();
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function jobLogs(): HasMany
    {
        return $this->hasMany(JobLog::class, 'entity_id')
            ->where('entity_type', 'business');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFetched($query)
    {
        return $query->where('status', 'fetched');
    }

    public function scopeReadyForAudit($query)
    {
        return $query->where('status', 'scraped');
    }

    public function scopeReadyForEmail($query)
    {
        return $query->where('status', 'audited');
    }

    public function scopeHasEmail($query)
    {
        return $query->whereNotNull('email');
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function markAs(string $status, ?string $message = null): void
    {
        $this->update(['status' => $status, 'status_message' => $message]);
    }

    public function hasSocialPresence(): bool
    {
        return ! empty($this->facebook_url)
            || ! empty($this->instagram_url)
            || ! empty($this->linkedin_url)
            || ! empty($this->twitter_url);
    }

    public function socialLinks(): array
    {
        return array_filter([
            'facebook'  => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'linkedin'  => $this->linkedin_url,
            'twitter'   => $this->twitter_url,
        ]);
    }
}
