<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MetodoPago;

$defaults = [
    ['id' => 1, 'nombre' => 'Efectivo', 'codigo' => 'efectivo'],
    ['id' => 3, 'nombre' => 'QR', 'codigo' => 'qr'],
];

foreach ($defaults as $d) {
    $exists = MetodoPago::find($d['id']);
    if ($exists) {
        echo "Existe: {$d['id']} - {$d['nombre']}\n";
        continue;
    }

    // Crear asegurando el id (solo si la DB lo permite)
    try {
        $mp = new MetodoPago();
        $mp->id = $d['id'];
        $mp->nombre = $d['nombre'];
        $mp->codigo = $d['codigo'];
        $mp->activo = 1;
        $mp->save();
        echo "Insertado: {$d['id']} - {$d['nombre']}\n";
    } catch (Exception $e) {
        echo "Error insertando {$d['id']}: " . $e->getMessage() . "\n";
    }
}

echo "Fin del script.\n";
