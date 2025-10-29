<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario admin
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'admin@panificadoranancy.com'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => Hash::make('admin123'), // Cambiar en producción
                'phone' => '77777777',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin = User::where('email', 'admin@panificadoranancy.com')->first();

        // Asignar rol de admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->hasRole('admin')) {
            $admin->roles()->attach($adminRole->id);
        }

        $this->command->info('Usuario admin creado: admin@panificadoranancy.com / admin123');
    }
}
