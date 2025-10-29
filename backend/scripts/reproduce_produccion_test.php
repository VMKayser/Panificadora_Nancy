<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\InventarioProductoFinal;

echo "Creating materias primas...\n";
$harina = MateriaPrima::create(['nombre'=>'Harina','stock_actual'=>100.0,'costo_unitario'=>1.0,'unidad_medida'=>'kg','activo'=>1]);
$azucar = MateriaPrima::create(['nombre'=>'Azúcar','stock_actual'=>50.0,'costo_unitario'=>2.0,'unidad_medida'=>'kg','activo'=>1]);
echo "Harina id: {$harina->id}, Azucar id: {$azucar->id}\n";

echo "Creating producto and receta...\n";
$producto = Producto::create(['categorias_id'=>1,'nombre'=>'TestProd','url'=>'testprod','descripcion'=>'x','descripcion_corta'=>'y','precio_minorista'=>10,'precio_mayorista'=>8,'cantidad_minima_mayoreo'=>0,'es_de_temporada'=>0,'esta_activo'=>1,'requiere_tiempo_anticipacion'=>0,'unidad_tiempo'=>null,'limite_produccion'=>0]);
$receta = Receta::create(['producto_id'=>$producto->id,'rendimiento'=>10,'nombre_receta'=>'Rec','unidad_rendimiento'=>'unidades']);

echo "Attaching ingredientes (harina 10, azucar 5)...\n";
$receta->ingredientes()->create(['materia_prima_id'=>$harina->id,'cantidad'=>10,'unidad'=>'kg']);
$receta->ingredientes()->create(['materia_prima_id'=>$azucar->id,'cantidad'=>5,'unidad'=>'kg']);

echo "Ingredientes DB:\n";
foreach($receta->ingredientes as $ing){
    echo "- id={$ing->id} mp_id={$ing->materia_prima_id} cantidad={$ing->cantidad} unidad={$ing->unidad}\n";
}

echo "Done.\n";
