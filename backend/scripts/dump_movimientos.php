<?php

// One-off debug script to dump recent MovimientoMateriaPrima entries
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MovimientoMateriaPrima;

$rows = MovimientoMateriaPrima::orderBy('id', 'desc')->limit(50)->get()->map(function($r) {
    return [
        'id' => $r->id,
        'materia_prima_id' => $r->materia_prima_id,
        'tipo' => $r->tipo_movimiento,
        'cantidad' => (float)$r->cantidad,
        'stock_anterior' => (float)$r->stock_anterior,
        'stock_nuevo' => (float)$r->stock_nuevo,
        'produccion_id' => $r->produccion_id,
        'created_at' => $r->created_at->toDateTimeString(),
    ];
})->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
