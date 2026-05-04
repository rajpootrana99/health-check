<?php

namespace App\Console\Commands;

use App\Models\JobLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneJobLogsCommand extends Command
{
    protected $signature   = 'audit:prune-logs {--days=30 : Delete logs older than this many days}';
    protected $description = 'Delete old job_logs entries to keep the table lean';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $count = JobLog::where('created_at', '<', $threshold)->delete();

        $this->info("Deleted {$count} job log entries older than {$days} days.");

        Log::info('PruneJobLogsCommand: pruned old job logs', [
            'deleted'   => $count,
            'older_than'=> $threshold->toDateString(),
        ]);

        return self::SUCCESS;
    }
}
