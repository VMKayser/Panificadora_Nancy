<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MateriaPrima;

$rows = MateriaPrima::where('nombre', 'like', '%Harina%')->get()->map(function($r){
    return [
        'id' => $r->id,
        'nombre' => $r->nombre,
        'stock_actual' => (float)$r->stock_actual,
        'created_at' => $r->created_at->toDateTimeString(),
    ];
})->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
