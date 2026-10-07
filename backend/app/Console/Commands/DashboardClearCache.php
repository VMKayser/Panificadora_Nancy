<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DashboardClearCache extends Command
{
    protected $signature = 'dashboard:clear-cache';
    protected $description = 'Clear the inventario.dashboard cache key';

    public function handle()
    {
        if (Cache::forget('inventario.dashboard')) {
            $this->info('inventario.dashboard cache cleared');
            return 0;
        }
        $this->info('inventario.dashboard cache did not exist or could not be cleared');
        return 0;
    }
}
