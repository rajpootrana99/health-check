<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'audit_data',
        'overall_score',
        'summary',
        'pdf_path',
        'pdf_url',
        'status',
        'ai_model_used',
        'ai_tokens_used',
        'generation_error',
        'generated_at',
    ];

    protected $casts = [
        'audit_data'     => 'array',
        'overall_score'  => 'integer',
        'ai_tokens_used' => 'integer',
        'generated_at'   => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /**
     * Returns the array of 15 health check results from audit_data.
     */
    public function getHealthChecksAttribute(): array
    {
        return $this->audit_data['checks'] ?? [];
    }

    public function getStrengthsAttribute(): array
    {
        return $this->audit_data['strengths'] ?? [];
    }

    public function getWeaknessesAttribute(): array
    {
        return $this->audit_data['weaknesses'] ?? [];
    }

    public function getOpportunitiesAttribute(): array
    {
        return $this->audit_data['opportunities'] ?? [];
    }

    public function getRecommendationsAttribute(): array
    {
        return $this->audit_data['recommendations'] ?? [];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->pdf_path !== null;
    }
}
