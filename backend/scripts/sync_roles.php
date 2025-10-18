<?php

use App\Models\User;

$users = User::all();
foreach ($users as $u) {
    $roles = $u->roles->pluck('name')->toArray();
    if (count($roles) > 0) {
        if (in_array('admin', $roles)) {
            $u->role = 'admin';
        } elseif (in_array('vendedor', $roles)) {
            $u->role = 'vendedor';
        } elseif (in_array('panadero', $roles)) {
            $u->role = 'panadero';
        } else {
            $u->role = 'cliente';
        }
        $u->saveQuietly();
    }
}

echo "Sincronizacion completa\n";
$admin = User::where('email', 'admin@panificadoranancy.com')->first();
echo 'Admin row: id=' . ($admin?->id) . ' role=' . ($admin?->role) . PHP_EOL;
