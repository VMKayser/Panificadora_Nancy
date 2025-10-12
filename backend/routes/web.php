<?php

use Illuminate\Support\Facades\Route;

// Servir la app React desde /app
Route::get('/app/{any?}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '.*')->name('app');

// Ruta de login (redirect a la app React)
Route::get('/login', function () {
    return redirect('/app');
})->name('login');

// Ruta raíz redirige a /app
Route::get('/', function () {
    return redirect('/app');
});
