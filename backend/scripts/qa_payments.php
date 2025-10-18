<?php
// QA script: run inside the app container to test empleado-pagos endpoints
// Usage (container): php backend/scripts/qa_payments.php

$vendor = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendor)) {
    // fallback to project root vendor
    $vendor = __DIR__ . '/../../vendor/autoload.php';
}
require $vendor;
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

function info($v){ echo json_encode($v, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n"; }

// Ensure models are available
if (!class_exists(\App\Models\User::class)) {
    echo "Models not found\n"; exit(1);
}

$admin = \App\Models\User::find(1);
if (!$admin) {
    echo "Admin user id=1 not found\n"; exit(1);
}

// Create token
$token = $admin->createToken('qa-token-' . time())->plainTextToken;
echo "Using token: " . $token . "\n\n";

$client = new Client(['base_uri' => 'http://localhost', 'http_errors' => false]);

// Helper to show panadero/vendedor
function showPanadero($id){
    $p = \App\Models\Panadero::find($id);
    if (!$p) return null;
    return ['id'=>$p->id, 'total_kilos_producidos'=> (float)$p->total_kilos_producidos, 'salario_por_kilo'=> (float)$p->salario_por_kilo];
}

function showVendedor($id){
    $v = \App\Models\Vendedor::find($id);
    if (!$v) return null;
    return ['id'=>$v->id, 'comision_acumulada'=> (float)$v->comision_acumulada, 'salario_base'=> (float)($v->salario_base ?? 0)];
}

// Test panadero payment
$panId = 2;
echo "-- PANADERO BEFORE (id={$panId}) --\n";
info(showPanadero($panId));

$payload = ['empleado_tipo'=>'panadero','empleado_id'=>$panId,'monto'=>50,'kilos_pagados'=>2,'tipo_pago'=>'pago_produccion','notas'=>'QA automatic panadero'];
$res = $client->post('/api/admin/empleado-pagos', [
    'headers'=>['Authorization' => "Bearer {$token}", 'Accept'=>'application/json'],
    'json'=>$payload,
]);

echo "-- RESPONSE PANADERO ({$res->getStatusCode()}) --\n";
echo (string)$res->getBody() . "\n";

echo "-- PANADERO AFTER (id={$panId}) --\n";
info(showPanadero($panId));

// Latest pago
$latest = \App\Models\EmpleadoPago::latest()->first();
echo "-- LATEST PAGO --\n";
if ($latest) info(['id'=>$latest->id, 'empleado_tipo'=>$latest->empleado_tipo, 'empleado_id'=>$latest->empleado_id, 'monto'=>(float)$latest->monto, 'kilos_pagados'=>(float)$latest->kilos_pagados, 'tipo_pago'=>$latest->tipo_pago, 'created_at'=>(string)$latest->created_at]);

// Test vendedor payment
$vendId = 1;
echo "\n-- VENDEDOR BEFORE (id={$vendId}) --\n";
info(showVendedor($vendId));

$payload2 = ['empleado_tipo'=>'vendedor','empleado_id'=>$vendId,'monto'=>30,'comision_pagada'=>30,'tipo_pago'=>'comision','notas'=>'QA automatic vendedor'];
$res2 = $client->post('/api/admin/empleado-pagos', [
    'headers'=>['Authorization' => "Bearer {$token}", 'Accept'=>'application/json'],
    'json'=>$payload2,
]);

echo "-- RESPONSE VENDEDOR ({$res2->getStatusCode()}) --\n";
echo (string)$res2->getBody() . "\n";

echo "-- VENDEDOR AFTER (id={$vendId}) --\n";
info(showVendedor($vendId));

echo "\nQA script finished\n";
