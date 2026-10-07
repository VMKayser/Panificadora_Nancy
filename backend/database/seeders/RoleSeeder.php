<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrador total del sistema'
            ],
            [
                'name' => 'panadero',
                'description' => 'Empleado de producción - maneja la elaboración de productos'
            ],
            [
                'name' => 'vendedor',
                'description' => 'Personal de tienda - puede ver y gestionar pedidos'
            ],
            [
                'name' => 'cliente',
                'description' => 'Usuario comprador - puede hacer pedidos'
            ]
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrInsert(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
