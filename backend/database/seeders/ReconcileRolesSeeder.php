<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Panadero;
use App\Models\Vendedor;

class ReconcileRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $created = 0;

        $this->command->info('Reconciling users with role=panadero...');
        $panaderos = User::where('role', 'panadero')->get();
        foreach ($panaderos as $u) {
            if (!$u->panadero) {
                // The `panaderos` table in this deployment doesn't include nombre/apellido/email
                // Insert only the columns that exist to avoid schema errors.
                Panadero::create([
                    'user_id' => $u->id,
                    'codigo_panadero' => Panadero::generarCodigoPanadero(),
                    'telefono' => $u->telefono ?? '0000000000',
                    'ci' => 'CI-' . $u->id,
                    'especialidad' => 'ambos',
                    'turno' => 'mañana',
                    'fecha_ingreso' => now(),
                    'activo' => true,
                    'salario_base' => 3000.00,
                ]);
                $created++;
                $this->command->info("Created panadero for user_id={$u->id}");
            }
        }

        $this->command->info('Reconciling users with role=vendedor...');
        $vendedores = User::where('role', 'vendedor')->get();
        foreach ($vendedores as $u) {
            if (!$u->vendedor) {
                Vendedor::create([
                    'user_id' => $u->id,
                    'codigo_vendedor' => Vendedor::generarCodigoVendedor(),
                    'comision_porcentaje' => 2.5,
                    'descuento_maximo_bs' => 50,
                    'puede_dar_descuentos' => true,
                    'puede_cancelar_ventas' => false,
                    'fecha_ingreso' => now(),
                    'estado' => 'activo',
                ]);
                $created++;
                $this->command->info("Created vendedor for user_id={$u->id}");
            }
        }

        $this->command->info("Reconciliation finished. Total created: {$created}");
    }
}
