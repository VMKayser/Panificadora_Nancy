<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario admin. La contraseña nunca es fija fuera de local/testing:
        // se toma de ADMIN_SEED_PASSWORD o se genera al azar y se muestra una sola vez.
        // Si el admin ya existe no se toca su contraseña.
        $existe = User::where('email', 'admin@panificadoranancy.com')->exists();
        $password = null;
        if (!$existe) {
            $password = env('ADMIN_SEED_PASSWORD')
                ?: (app()->environment('local', 'testing') ? 'admin123' : Str::password(20));
            \App\Models\User::query()->insert([
                'email' => 'admin@panificadoranancy.com',
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => Hash::make($password),
                'phone' => '77777777',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $admin = User::where('email', 'admin@panificadoranancy.com')->first();

        // Asignar rol de admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->hasRole('admin')) {
            $admin->roles()->attach($adminRole->id);
        }

        if ($password !== null && !env('ADMIN_SEED_PASSWORD')) {
            $this->command?->warn("Usuario admin creado: admin@panificadoranancy.com / {$password} (cámbiala al ingresar)");
        } elseif ($password === null) {
            $this->command?->info('Usuario admin ya existía: contraseña sin cambios.');
        }
    }
}
