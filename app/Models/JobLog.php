<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'job_class',
        'queue',
        'status',
        'attempt',
        'payload',
        'output',
        'error',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'attempt'     => 'integer',
        'duration_ms' => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public static function start(string $jobClass, string $entityType, int $entityId, array $payload = []): static
    {
        return static::create([
            'job_class'   => $jobClass,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'queue'       => 'default',
            'status'      => 'processing',
            'payload'     => json_encode($payload),
            'started_at'  => now(),
        ]);
    }

    public function finish(string $output = ''): void
    {
        $this->update([
            'status'      => 'completed',
            'output'      => $output,
            'finished_at' => now(),
            'duration_ms' => $this->started_at
                ? (int) $this->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }

    public function fail(string $error): void
    {
        $this->update([
            'status'      => 'failed',
            'error'       => $error,
            'finished_at' => now(),
            'duration_ms' => $this->started_at
                ? (int) $this->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }
}
