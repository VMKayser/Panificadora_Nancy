<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Cliente;
use App\Models\Panadero;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Panificador/Vendedor
        $panificador = User::firstOrCreate(
            ['email' => 'vendedor@panificadoranancy.com'],
            [
                'name' => 'Carlos Panificador',
                'password' => Hash::make('vendedor123'),
                'phone' => '65551234',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol de vendedor
        $vendedorRole = Role::where('name', 'vendedor')->first();
        if ($vendedorRole && !$panificador->hasRole('vendedor')) {
            $panificador->roles()->attach($vendedorRole->id);
        }

        // Crear registro en tabla panaderos
        Panadero::firstOrCreate(
            ['email' => 'vendedor@panificadoranancy.com'],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Panificador',
                'telefono' => '65551234',
                'ci' => '5555555 LP',
                'direccion' => 'Calle Los Hornos #123, La Paz',
                'fecha_ingreso' => '2024-01-15',
                'turno' => 'mañana',
                'especialidad' => 'ambos',
                'salario_base' => 3500.00,
                'activo' => true,
                'observaciones' => 'Panadero con 10 años de experiencia'
            ]
        );

        // Usuario Cliente 1
        $cliente1 = User::firstOrCreate(
            ['email' => 'maria@cliente.com'],
            [
                'name' => 'María García',
                'password' => Hash::make('cliente123'),
                'phone' => '65559876',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol de cliente
        $clienteRole = Role::where('name', 'cliente')->first();
        if ($clienteRole && !$cliente1->hasRole('cliente')) {
            $cliente1->roles()->attach($clienteRole->id);
        }

        // Crear registro en tabla clientes
        Cliente::firstOrCreate(
            ['email' => 'maria@cliente.com'],
            [
                'nombre' => 'María',
                'apellido' => 'García',
                'telefono' => '65559876',
                'direccion' => 'Av. 6 de Agosto #123, La Paz',
                'ci' => '7654321 LP',
                'tipo_cliente' => 'regular',
                'activo' => true,
            ]
        );

        // Usuario Cliente 2
        $cliente2 = User::firstOrCreate(
            ['email' => 'juan@cliente.com'],
            [
                'name' => 'Juan Pérez',
                'password' => Hash::make('cliente123'),
                'phone' => '65555555',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($clienteRole && !$cliente2->hasRole('cliente')) {
            $cliente2->roles()->attach($clienteRole->id);
        }

        // Crear registro en tabla clientes
        Cliente::firstOrCreate(
            ['email' => 'juan@cliente.com'],
            [
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'telefono' => '65555555',
                'direccion' => 'Calle Comercio #456, La Paz',
                'ci' => '9876543 LP',
                'tipo_cliente' => 'regular',
                'activo' => true,
            ]
        );

        // Usuario Cliente 3
        $cliente3 = User::firstOrCreate(
            ['email' => 'ana@cliente.com'],
            [
                'name' => 'Ana López',
                'password' => Hash::make('cliente123'),
                'phone' => '65554321',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($clienteRole && !$cliente3->hasRole('cliente')) {
            $cliente3->roles()->attach($clienteRole->id);
        }

        // Crear registro en tabla clientes
        Cliente::firstOrCreate(
            ['email' => 'ana@cliente.com'],
            [
                'nombre' => 'Ana',
                'apellido' => 'López',
                'telefono' => '65554321',
                'direccion' => 'Zona Sur, Calle 21 #789, La Paz',
                'ci' => '1234567 LP',
                'tipo_cliente' => 'mayorista',
                'activo' => true,
            ]
        );

        $this->command->info('Usuarios de prueba creados:');
        $this->command->info('- Vendedor: vendedor@panificadoranancy.com / vendedor123');
        $this->command->info('- Cliente 1: maria@cliente.com / cliente123 (Regular)');
        $this->command->info('- Cliente 2: juan@cliente.com / cliente123 (Regular)');
        $this->command->info('- Cliente 3: ana@cliente.com / cliente123 (Mayorista)');
        $this->command->info('');
        $this->command->info('✅ Registros creados en tabla users Y tabla clientes');
    }
}