<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $userEmail = 'valenciamedinafreddydaniel1@gmail.com';
    \Illuminate\Support\Facades\Mail::raw('Prueba SMTP desde contenedor', function($m) use ($userEmail) {
        $m->to($userEmail)->subject('Prueba SMTP');
    });
    echo "MAIL_SENT_OK\n";
} catch (Throwable $e) {
    echo "MAIL_ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
