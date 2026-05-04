<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_type',
        'location',
        'label',
        'status',
        'total_businesses',
        'audited_count',
        'emailed_count',
        'notes',
    ];

    protected $casts = [
        'total_businesses' => 'integer',
        'audited_count'    => 'integer',
        'emailed_count'    => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Boot — auto-generate label if not provided
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (empty($campaign->label)) {
                $campaign->label = ucfirst($campaign->business_type) . ' in ' . $campaign->location;
            }
        });
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['fetching', 'processing']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function markAs(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function refreshCounts(): void
    {
        $this->update([
            'total_businesses' => $this->businesses()->count(),
            'audited_count'    => $this->businesses()->where('status', 'audited')->count(),
            'emailed_count'    => $this->businesses()->where('status', 'emailed')->count(),
        ]);
    }
}
