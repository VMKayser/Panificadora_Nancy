<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Produccion;

echo "Simulating production...\n";

$harina = MateriaPrima::create(['nombre'=>'Harina','stock_actual'=>100.0,'costo_unitario'=>1.0,'unidad_medida'=>'kg','activo'=>1]);
$azucar = MateriaPrima::create(['nombre'=>'Azúcar','stock_actual'=>50.0,'costo_unitario'=>2.0,'unidad_medida'=>'kg','activo'=>1]);

$producto = Producto::create(['categorias_id'=>1,'nombre'=>'TestProd','url'=>'testprod2','descripcion'=>'x','descripcion_corta'=>'y','precio_minorista'=>10,'precio_mayorista'=>8,'cantidad_minima_mayoreo'=>0,'es_de_temporada'=>0,'esta_activo'=>1,'requiere_tiempo_anticipacion'=>0,'unidad_tiempo'=>null,'limite_produccion'=>0]);
$receta = Receta::create(['producto_id'=>$producto->id,'rendimiento'=>10,'nombre_receta'=>'Rec','unidad_rendimiento'=>'unidades']);
$receta->ingredientes()->create(['materia_prima_id'=>$harina->id,'cantidad'=>10,'unidad'=>'kg']);
$receta->ingredientes()->create(['materia_prima_id'=>$azucar->id,'cantidad'=>5,'unidad'=>'kg']);

$produccion = Produccion::create([
    'producto_id' => $producto->id,
    'receta_id' => $receta->id,
    'user_id' => 1,
    'fecha_produccion' => date('Y-m-d'),
    'cantidad_producida' => 10,
    'unidad' => 'unidades',
    'harina_real_usada' => 12,
    'estado' => 'en_proceso',
]);

$produccion->procesar([['materia_prima_id' => $azucar->id, 'cantidad' => 2]]);

$harina->refresh();
$azucar->refresh();
echo "Final Harina stock: {$harina->stock_actual}\n";
echo "Final Azucar stock: {$azucar->stock_actual}\n";
