<?php

use App\Console\Commands\DispatchWeeklyEmailsCommand;
use App\Console\Commands\ProcessStalledJobsCommand;
use App\Console\Commands\PruneJobLogsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Exclude the Resend webhook endpoint from CSRF verification.
        // The endpoint is protected by svix signature verification instead.
        $middleware->validateCsrfTokens(except: [
            'webhooks/resend',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // ------------------------------------------------------------------
        // Weekly email dispatch — Monday 09:00 (configurable via .env)
        // ------------------------------------------------------------------
        $sendDay  = config('audit.outreach.send_day', 'monday');
        $sendTime = config('audit.outreach.send_time', '09:00');

        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6,
        ];
        $dayNumber = $dayMap[strtolower($sendDay)] ?? 1;

        $schedule->command(DispatchWeeklyEmailsCommand::class)
            ->weeklyOn($dayNumber, $sendTime)
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Stall detector — every hour
        // ------------------------------------------------------------------
        $schedule->command(ProcessStalledJobsCommand::class, ['--stall-minutes=120'])
            ->hourly()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Log pruning — Sunday 02:00
        // ------------------------------------------------------------------
        $schedule->command(PruneJobLogsCommand::class, ['--days=30'])
            ->weeklyOn(0, '02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ------------------------------------------------------------------
        // Laravel built-ins
        // ------------------------------------------------------------------
        $schedule->command('queue:prune-failed --hours=168')->daily();
        $schedule->command('auth:clear-resets')->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
