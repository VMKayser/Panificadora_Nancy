<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Borrar de la BD los tokens de Sanctum vencidos hace más de un día.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
