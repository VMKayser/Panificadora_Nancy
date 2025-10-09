<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sincronizar usuarios con rol 'cliente' a la tabla clientes
        $clienteRoleId = DB::table('roles')->where('name', 'cliente')->value('id');
        
        if (!$clienteRoleId) {
            return; // Si no existe el rol, no hacer nada
        }

        // Obtener usuarios con rol cliente que no estén en la tabla clientes
        $usuariosCliente = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', $clienteRoleId)
            ->whereNotIn('users.email', function($query) {
                $query->select('email')->from('clientes');
            })
            ->select('users.*')
            ->get();

        foreach ($usuariosCliente as $user) {
            // Separar nombre y apellido
            $nombreCompleto = explode(' ', $user->name, 2);
            $nombre = $nombreCompleto[0];
            $apellido = $nombreCompleto[1] ?? '';

            DB::table('clientes')->insert([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $user->email,
                'telefono' => $user->phone ?? null,
                'tipo_cliente' => 'regular',
                'activo' => $user->is_active ?? true,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en down, no queremos eliminar clientes
    }
};
