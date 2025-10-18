<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\AdminVendedorController;
use Illuminate\Http\Request;

$ctrl = app()->make(AdminVendedorController::class);

$payload = [
    'nombre' => 'TestV',
    'apellido' => 'User',
    'email' => 'testvendedor+' . time() . '@example.com',
    'telefono' => '87654321',
    'ci' => 'CI-V' . time(),
    'fecha_ingreso' => date('Y-m-d'),
    'turno' => 'tarde',
    'comision_porcentaje' => 5,
    'salario_base' => 1500,
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
