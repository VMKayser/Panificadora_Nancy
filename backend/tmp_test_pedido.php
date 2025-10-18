<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;

$controller = new App\Http\Controllers\Api\PedidoController();

// Simular request
$req = Request::create('/api/pedidos', 'POST', [
    'cliente_nombre' => 'Test',
    'cliente_apellido' => 'User',
    'cliente_email' => 'test@example.com',
    'cliente_telefono' => '123456',
    'tipo_entrega' => 'delivery',
    'metodos_pago_id' => 3,
    'productos' => [ ['id' => 1, 'cantidad' => 1] ],
]);

$response = $controller->store($req);
http_response_code($response->getStatusCode());
echo json_encode($response->getData(), JSON_PRETTY_PRINT);

