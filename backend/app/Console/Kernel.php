<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SyncRoles::class,
        \App\Console\Commands\PoblarInventarioProductos::class,
        // Temporary inventory simulation commands removed; keep test files instead
        \App\Console\Commands\GenerateDashboardSnapshot::class,
        \App\Console\Commands\DashboardClearCache::class,
        \App\Console\Commands\IntegrityFixVendedores::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate dashboard snapshot every 5 minutes (cached JSON)
        $schedule->command('dashboard:generate')->everyFiveMinutes();
        
        // Process queued jobs on shared hosting via cron: schedule:run -> queue:work --stop-when-empty
        // This will run the worker until the queue is empty and then exit. Cron should call schedule:run every minute.
        $schedule->command('queue:work --stop-when-empty --tries=3 --sleep=3 --timeout=120')
            ->withoutOverlapping()
            ->runInBackground()
            ->everyMinute()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/queue-schedule.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
