<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Add this to your server crontab (runs every minute):
     *   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
     */
    protected function schedule(Schedule $schedule): void
    {
        // ------------------------------------------------------------------
        // Weekly email dispatch
        //
        // Runs every Monday at 09:00 (configurable via EMAIL_SEND_DAY / TIME).
        // Picks up to WEEKLY_EMAIL_LIMIT pending EmailLogs and queues them.
        // withoutOverlapping() ensures a slow run doesn't trigger a second one.
        // ------------------------------------------------------------------
        $sendDay  = config('audit.outreach.send_day', 'monday');
        $sendTime = config('audit.outreach.send_time', '09:00');

        $schedule->command('audit:dispatch-weekly-emails')
            ->weeklyOn($this->dayNumberFor($sendDay), $sendTime)
            ->withoutOverlapping(10)          // lock for max 10 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Stall detector — runs every hour
        //
        // Re-queues businesses stuck in 'scraping' or 'auditing' for > 2h.
        // Protects against silent job failures that leave the pipeline frozen.
        // ------------------------------------------------------------------
        $schedule->command('audit:process-stalled --stall-minutes=120')
            ->hourly()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Log pruning — runs every Sunday at 02:00
        //
        // Keeps job_logs table lean by removing entries older than 30 days.
        // ------------------------------------------------------------------
        $schedule->command('audit:prune-logs --days=30')
            ->weeklyOn(0, '02:00')           // 0 = Sunday
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Laravel built-ins
        // ------------------------------------------------------------------

        // Prune stale Laravel telescope entries (if Telescope is installed)
        // $schedule->command('telescope:prune')->daily();

        // Clear expired password reset tokens
        $schedule->command('auth:clear-resets')
            ->weekly();

        // Prune failed jobs older than 7 days from the failed_jobs table
        $schedule->command('queue:prune-failed --hours=168')
            ->daily()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    // -----------------------------------------------------------------------

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Convert a day name to Carbon's weeklyOn() integer (0=Sun … 6=Sat).
     */
    private function dayNumberFor(string $day): int
    {
        return match (strtolower(trim($day))) {
            'sunday'    => 0,
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            default     => 1, // default Monday
        };
    }
}
