<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Cliente;
use App\Models\Panadero;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Panificador/Vendedor
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'vendedor@panificadoranancy.com'],
            [
                'name' => 'Carlos Panificador',
                'password' => Hash::make('vendedor123'),
                'phone' => '65551234',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $panificador = User::where('email', 'vendedor@panificadoranancy.com')->first();

        // Asignar rol de vendedor
        $vendedorRole = Role::where('name', 'vendedor')->first();
        if ($vendedorRole && !$panificador->hasRole('vendedor')) {
            $panificador->roles()->attach($vendedorRole->id);
        }

        // Crear registro en tabla panaderos - vincular por user_id si la columna existe
        // Only include columns that remain on the panaderos table (contact info lives on users)
        $panaderoData = [
            'direccion' => 'Calle Los Hornos #123, La Paz',
            'fecha_ingreso' => '2024-01-15',
            'turno' => 'mañana',
            'especialidad' => 'ambos',
            'salario_base' => 3500.00,
            'activo' => true,
            'observaciones' => 'Panadero con 10 años de experiencia'
        ];

        // Crear usuario Panadero (separado del vendedor) si no existe
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'panadero@panificadoranancy.com'],
            [
                'name' => 'Pedro Panadero',
                'password' => Hash::make('panadero123'),
                'phone' => '65550001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $panaderoUser = User::where('email', 'panadero@panificadoranancy.com')->first();

        // Asignar rol de panadero al usuario panadero
        $panaderoRole = Role::where('name', 'panadero')->first();
        if ($panaderoRole && !$panaderoUser->hasRole('panadero')) {
            $panaderoUser->roles()->attach($panaderoRole->id);
        }

        try {
            // If panaderos table has user_id, link the Panadero to the created User
            if (Schema::hasColumn('panaderos', 'user_id')) {
                    \App\Models\Panadero::query()->updateOrInsert(
                        ['user_id' => $panaderoUser->id],
                        array_merge($panaderoData, ['user_id' => $panaderoUser->id, 'codigo_panadero' => Panadero::generarCodigoPanadero()])
                    );
            } else {
                // Fallback: older schema where panaderos had email field
                    \App\Models\Panadero::query()->updateOrInsert(
                        ['email' => 'vendedor@panificadoranancy.com'],
                        $panaderoData
                    );
            }
        } catch (\Exception $e) {
            // If migration state is unexpected, log and continue without failing the seeder
            $this->command->error('Warning: could not create panadero record: ' . $e->getMessage());
        }

        // Usuario Cliente 1
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'maria@cliente.com'],
            [
                'name' => 'María García',
                'password' => Hash::make('cliente123'),
                'phone' => '65559876',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $cliente1 = User::where('email', 'maria@cliente.com')->first();

        // Asignar rol de cliente
        $clienteRole = Role::where('name', 'cliente')->first();
        if ($clienteRole && !$cliente1->hasRole('cliente')) {
            $cliente1->roles()->attach($clienteRole->id);
        }

        // Crear registro en tabla clientes - vincular por user_id si la columna existe
        $clienteData1 = [
            'nombre' => 'María',
            'apellido' => 'García',
            'telefono' => '65559876',
            'direccion' => 'Av. 6 de Agosto #123, La Paz',
            'ci' => '7654321 LP',
            'tipo_cliente' => 'regular',
            'activo' => true,
        ];

        try {
            if (Schema::hasColumn('clientes', 'user_id')) {
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['user_id' => $cliente1->id],
                        array_merge($clienteData1, ['user_id' => $cliente1->id, 'email' => $cliente1->email])
                    );
                } else {
                    // Fallback for older schema: use query builder upsert to avoid nested transactions
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['email' => 'maria@cliente.com'],
                        $clienteData1
                    );
                }
        } catch (\Exception $e) {
            $this->command->error('Warning: could not create cliente (maria): ' . $e->getMessage());
        }

        // Usuario Cliente 2
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'juan@cliente.com'],
            [
                'name' => 'Juan Pérez',
                'password' => Hash::make('cliente123'),
                'phone' => '65555555',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $cliente2 = User::where('email', 'juan@cliente.com')->first();

        if ($clienteRole && !$cliente2->hasRole('cliente')) {
            $cliente2->roles()->attach($clienteRole->id);
        }

        $clienteData2 = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'telefono' => '65555555',
            'direccion' => 'Calle Comercio #456, La Paz',
            'ci' => '9876543 LP',
            'tipo_cliente' => 'regular',
            'activo' => true,
        ];

        try {
            if (Schema::hasColumn('clientes', 'user_id')) {
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['user_id' => $cliente2->id],
                        array_merge($clienteData2, ['user_id' => $cliente2->id, 'email' => $cliente2->email])
                    );
                } else {
                    // Fallback for older schema: use query builder upsert to avoid nested transactions
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['email' => 'juan@cliente.com'],
                        $clienteData2
                    );
                }
        } catch (\Exception $e) {
            $this->command->error('Warning: could not create cliente (juan): ' . $e->getMessage());
        }

        // Usuario Cliente 3
        \App\Models\User::query()->updateOrInsert(
            ['email' => 'ana@cliente.com'],
            [
                'name' => 'Ana López',
                'password' => Hash::make('cliente123'),
                'phone' => '65554321',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $cliente3 = User::where('email', 'ana@cliente.com')->first();

        if ($clienteRole && !$cliente3->hasRole('cliente')) {
            $cliente3->roles()->attach($clienteRole->id);
        }

        $clienteData3 = [
            'nombre' => 'Ana',
            'apellido' => 'López',
            'telefono' => '65554321',
            'direccion' => 'Zona Sur, Calle 21 #789, La Paz',
            'ci' => '1234567 LP',
            'tipo_cliente' => 'mayorista',
            'activo' => true,
        ];

        try {
            if (Schema::hasColumn('clientes', 'user_id')) {
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['user_id' => $cliente3->id],
                        array_merge($clienteData3, ['user_id' => $cliente3->id, 'email' => $cliente3->email])
                    );
                } else {
                    // Fallback for older schema: use query builder upsert to avoid nested transactions
                    \App\Models\Cliente::query()->updateOrInsert(
                        ['email' => 'ana@cliente.com'],
                        $clienteData3
                    );
                }
        } catch (\Exception $e) {
            $this->command->error('Warning: could not create cliente (ana): ' . $e->getMessage());
        }

        $this->command->info('Usuarios de prueba creados:');
        $this->command->info('- Vendedor: vendedor@panificadoranancy.com / vendedor123');
        $this->command->info('- Cliente 1: maria@cliente.com / cliente123 (Regular)');
        $this->command->info('- Cliente 2: juan@cliente.com / cliente123 (Regular)');
        $this->command->info('- Cliente 3: ana@cliente.com / cliente123 (Mayorista)');
        $this->command->info('');
        $this->command->info('✅ Registros creados en tabla users Y tabla clientes');
    }
}