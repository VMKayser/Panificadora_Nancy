<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Vendedor;

class IntegrityFixVendedores extends Command
{
    protected $signature = 'integrity:fix-vendedores {--remove-orphans}';
    protected $description = 'Crea filas faltantes en vendedores para usuarios con rol vendedor y opcionalmente elimina filas huérfanas.';

    public function handle()
    {
        $this->info('Buscando usuarios con rol vendedor...');
        $roleId = DB::table('roles')->where('name','vendedor')->value('id');
        if (!$roleId) {
            $this->error('Rol vendedor no encontrado.');
            return 1;
        }

        $userIds = DB::table('role_user')->where('role_id',$roleId)->pluck('user_id')->toArray();
        $created = [];
        foreach ($userIds as $uid) {
            $exists = DB::table('vendedores')->where('user_id',$uid)->exists();
            if (!$exists) {
                DB::table('vendedores')->insert([
                    'user_id' => $uid,
                    'codigo_vendedor' => Vendedor::generarCodigoVendedor(),
                    'comision_porcentaje' => 5.00,
                    'comision_acumulada' => 0,
                    'descuento_maximo_bs' => 10,
                    'puede_dar_descuentos' => 1,
                    'puede_cancelar_ventas' => 0,
                    'turno' => 'mañana',
                    'fecha_ingreso' => now(),
                    'estado' => 'activo',
                    'observaciones' => 'Creado por integrity:fix-vendedores',
                    'ventas_realizadas' => 0,
                    'total_vendido' => 0,
                    'descuentos_otorgados' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created[] = $uid;
            }
        }

        $this->info('Filas creadas: ' . count($created));
        if (count($created) > 0) $this->info('User IDs creados: ' . implode(',', $created));

        if ($this->option('remove-orphans')) {
            $this->info('Eliminando filas huérfanas en vendedores (sin rol vendedor)...');
            $affected = DB::delete('DELETE v FROM vendedores v LEFT JOIN role_user ru ON v.user_id = ru.user_id WHERE ru.role_id IS NULL OR ru.role_id <> ?', [$roleId]);
            $this->info('Filas huérfanas eliminadas: ' . $affected);
        }

        $this->info('Integrity fix complete.');
        return 0;
    }
}
