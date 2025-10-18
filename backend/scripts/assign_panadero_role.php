<?php

use Illuminate\Support\Facades\DB;

$panaderoRole = DB::table('roles')->where('name', 'panadero')->first();
if (!$panaderoRole) {
    echo "Role panadero no encontrado\n";
    return;
}

foreach (App\Models\Panadero::with('user')->get() as $p) {
    if ($p->user) {
        $exists = DB::table('role_user')->where('user_id', $p->user->id)->where('role_id', $panaderoRole->id)->exists();
        if (! $exists) {
            DB::table('role_user')->insert(['user_id' => $p->user->id, 'role_id' => $panaderoRole->id]);
            echo "Asignado pivot panadero a user_id={$p->user->id}\n";
        }
    }
}

echo "Asignacion completada\n";
