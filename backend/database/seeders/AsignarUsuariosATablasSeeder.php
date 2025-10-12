<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Panadero;
use Illuminate\Support\Facades\DB;

class AsignarUsuariosATablasSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('Asignando usuarios a sus tablas específicas...');

        // Obtener todos los usuarios con roles
        $usuarios = User::with('roles')->get();

        foreach ($usuarios as $usuario) {
            $rolesNombres = $usuario->roles->pluck('name')->toArray();

            // Si tiene rol vendedor y no existe en tabla vendedores
            if (in_array('vendedor', $rolesNombres) && !$usuario->vendedor) {
                Vendedor::create([
                    'user_id' => $usuario->id,
                    'codigo_vendedor' => Vendedor::generarCodigoVendedor(),
                    'comision_porcentaje' => 2.5,
                    'descuento_maximo_bs' => 50,
                    'puede_dar_descuentos' => true,
                    'puede_cancelar_ventas' => false,
                    'fecha_ingreso' => now(),
                    'estado' => 'activo',
                    'turno' => 'mañana',
                ]);
                $this->command->info("✓ Vendedor creado para: {$usuario->name}");
            }

            // Si tiene rol panadero y no existe en tabla panaderos
            if (in_array('panadero', $rolesNombres)) {
                // Verificar si ya existe por email (legacy) o user_id
                $panaderoExistente = Panadero::where('email', $usuario->email)
                    ->orWhere('user_id', $usuario->id)
                    ->first();

                if (!$panaderoExistente) {
                    Panadero::create([
                        'user_id' => $usuario->id,
                        'codigo_panadero' => Panadero::generarCodigoPanadero(),
                        'nombre' => explode(' ', $usuario->name)[0] ?? $usuario->name,
                        'apellido' => explode(' ', $usuario->name)[1] ?? '',
                        'email' => $usuario->email,
                        'telefono' => $usuario->phone ?? '00000000',
                        'ci' => 'N/A',
                        'especialidad' => 'general',
                        'turno' => 'mañana',
                        'salario_base' => 2000,
                        'fecha_ingreso' => now(),
                        'activo' => true,
                    ]);
                    $this->command->info("✓ Panadero creado para: {$usuario->name}");
                } elseif (!$panaderoExistente->user_id) {
                    // Si existe pero no tiene user_id, actualizarlo
                    $panaderoExistente->update([
                        'user_id' => $usuario->id,
                        'codigo_panadero' => $panaderoExistente->codigo_panadero ?? Panadero::generarCodigoPanadero(),
                    ]);
                    $this->command->info("✓ Panadero actualizado con user_id: {$usuario->name}");
                }
            }
        }

        $this->command->info('✓ Proceso completado!');
    }
}
