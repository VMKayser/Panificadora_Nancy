<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\ClienteController;
use Illuminate\Http\Request;

$ctrl = app()->make(ClienteController::class);

$payload = [
    'nombre' => 'ClienteTest',
    'apellido' => 'C',
    'email' => 'testcliente+' . time() . '@example.com',
    'telefono' => '44455566',
    'ci' => 'CI-C' . time(),
    'tipo_cliente' => 'regular',
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
