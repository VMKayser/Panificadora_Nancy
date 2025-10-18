<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MetodoPago;

$defaults = [
    ['id' => 1, 'nombre' => 'Efectivo', 'codigo' => 'efectivo', 'esta_activo' => 1, 'orden' => 1],
    ['id' => 3, 'nombre' => 'QR', 'codigo' => 'qr', 'esta_activo' => 1, 'orden' => 3],
];

foreach ($defaults as $d) {
    $exists = MetodoPago::find($d['id']);
    if ($exists) {
        echo "Existe: {$d['id']} - {$d['nombre']}\n";
        continue;
    }

    try {
        $mp = new MetodoPago();
        // Si la BD no permite asignar id manualmente, quitar esta línea
        $mp->id = $d['id'];
        $mp->nombre = $d['nombre'];
        $mp->codigo = $d['codigo'];
        $mp->esta_activo = $d['esta_activo'];
        $mp->orden = $d['orden'];
        $mp->save();
        echo "Insertado: {$d['id']} - {$d['nombre']}\n";
    } catch (Exception $e) {
        echo "Error insertando {$d['id']}: " . $e->getMessage() . "\n";
    }
}

echo "Fin del script.\n";
