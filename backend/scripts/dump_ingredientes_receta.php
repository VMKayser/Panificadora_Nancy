<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\IngredienteReceta;

$rows = IngredienteReceta::orderBy('id','desc')->limit(50)->get()->map(function($r){
    return [
        'id'=>$r->id,
        'receta_id'=>$r->receta_id,
        'materia_prima_id'=>$r->materia_prima_id,
        'cantidad'=>(float)$r->cantidad,
        'unidad'=>$r->unidad,
    ];
})->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
