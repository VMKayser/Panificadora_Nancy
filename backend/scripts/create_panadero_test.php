<?php
// Simple test script to call AdminPanaderoController@store
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\AdminPanaderoController;
use Illuminate\Http\Request;

$ctrl = app()->make(AdminPanaderoController::class);

$payload = [
    'nombre' => 'Test',
    'apellido' => 'User',
    'email' => 'testpanadero+' . time() . '@example.com',
    'telefono' => '12345678',
    'ci' => 'CI-' . time(),
    'fecha_ingreso' => date('Y-m-d'),
    'turno' => 'mañana',
    'especialidad' => 'pan',
    'salario_base' => 2000,
];

$request = Request::create('/', 'POST', $payload);
try {
    $response = $ctrl->store($request);
    echo "Status: " . ($response->getStatusCode() ?? 'n/a') . PHP_EOL;
    echo (string) $response->getContent() . PHP_EOL;
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
