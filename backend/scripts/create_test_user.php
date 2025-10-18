<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;

try {
    $u = User::create([
        'name' => 'Wyj Test',
        'email' => 'wyjezomibo@mailinator.com',
        'password' => Hash::make('Prueba123'),
        'is_active' => true,
    ]);
    echo "Created user ID: {$u->id}\n";
    $c = Cliente::where('email', 'wyjezomibo@mailinator.com')->first();
    if ($c) {
        echo "Cliente created with ID: {$c->id}, telefono: '" . ($c->telefono ?? 'NULL') . "', apellido: '" . ($c->apellido ?? 'NULL') . "'\n";
    } else {
        echo "Cliente not found after creating user.\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

