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
