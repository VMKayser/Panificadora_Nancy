<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

$payload = [
    'cliente_nombre' => 'Cliente Prueba',
    'cliente_email' => 'venta_test@local.panificadoranancy.com',
    'cliente_telefono' => '00000000',
    'metodos_pago_id' => 1,
    'tipo_entrega' => 'recoger',
    'es_venta_mostrador' => true,
    'estado' => 'entregado',
    'descuento_bs' => 0,
    'motivo_descuento' => null,
    'detalles' => [
        ['producto_id' => 1, 'cantidad' => 1, 'precio_unitario' => 1.00, 'subtotal' => 1.00]
    ],
    'subtotal' => 1.00,
    'total' => 1.00
];

// Hacer request interno usando Http client a la API (localhost)
try {
    $response = Http::withHeaders(['Accept' => 'application/json'])->post('http://localhost/api/pedidos', $payload);
    echo "Status: " . $response->status() . "\n";
    echo $response->body() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
