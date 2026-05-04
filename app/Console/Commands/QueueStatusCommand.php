<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\EmailLog;
use App\Models\JobLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class QueueStatusCommand extends Command
{
    protected $signature   = 'queue:status';
    protected $description = 'Display a summary of queue health and pipeline progress';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>  Business Audit — Queue Status</>');
        $this->line('  ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // ------------------------------------------------------------------
        // Redis queue depths
        // ------------------------------------------------------------------
        $this->line('<options=bold>  Queue Depths (Redis)</>');

        $queues = ['default', 'scraping', 'ai', 'email'];
        $rows   = [];

        foreach ($queues as $queue) {
            try {
                $pending  = Redis::llen("queues:{$queue}");
                $delayed  = Redis::zcard("queues:{$queue}:delayed");
                $reserved = Redis::zcard("queues:{$queue}:reserved");

                $rows[] = [$queue, $pending, $delayed, $reserved];
            } catch (\Throwable) {
                $rows[] = [$queue, '—', '—', '— (Redis unavailable)'];
            }
        }

        $this->table(['Queue', 'Pending', 'Delayed', 'Reserved'], $rows);

        // ------------------------------------------------------------------
        // Pipeline progress (business status machine)
        // ------------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>  Pipeline Progress (Businesses)</>');

        $statuses = [
            'pending', 'fetched', 'scraping', 'scraped',
            'auditing', 'audited', 'emailed', 'failed',
        ];

        $counts = Business::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->table(
            ['Status', 'Count'],
            collect($statuses)->map(fn ($s) => [
                $s,
                $counts->get($s, 0),
            ])->all()
        );

        // ------------------------------------------------------------------
        // Email this week
        // ------------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>  Outreach This Week</>');

        $limit    = (int) config('audit.outreach.weekly_limit', 10);
        $sent     = EmailLog::sentThisWeek()->count();
        $opened   = EmailLog::sentThisWeek()->where('opened', true)->count();
        $clicked  = EmailLog::sentThisWeek()->where('clicked', true)->count();
        $openRate = $sent > 0 ? round(($opened / $sent) * 100, 1) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Sent this week',       "{$sent} / {$limit}"],
                ['Remaining this week',  max(0, $limit - $sent)],
                ['Opened',               "{$opened} ({$openRate}%)"],
                ['Clicked',              $clicked],
            ]
        );

        // ------------------------------------------------------------------
        // Recent job failures (last 24h)
        // ------------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>  Recent Job Failures (last 24h)</>');

        $failures = JobLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['job_class', 'entity_type', 'entity_id', 'error', 'created_at']);

        if ($failures->isEmpty()) {
            $this->line('  <fg=green>No failures in the last 24 hours.</>');
        } else {
            $this->table(
                ['Job', 'Entity', 'Error', 'When'],
                $failures->map(fn ($f) => [
                    class_basename($f->job_class),
                    "{$f->entity_type}#{$f->entity_id}",
                    \Illuminate\Support\Str::limit($f->error ?? '—', 60),
                    $f->created_at->diffForHumans(),
                ])->all()
            );
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
