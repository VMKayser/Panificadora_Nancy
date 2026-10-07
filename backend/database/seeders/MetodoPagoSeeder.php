<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('metodos_pago')) {
            Log::warning('MetodoPagoSeeder: table metodos_pago does not exist, skipping.');
            return;
        }

        $metodosPago = config('metodos_pago.defaults', []);
        if (empty($metodosPago)) {
            Log::info('MetodoPagoSeeder: no defaults configured, skipping.');
            return;
        }

        $now = now();
        $payload = collect($metodosPago)->map(function ($method, $index) use ($now) {
            return [
                'nombre' => $method['nombre'],
                'codigo' => $method['codigo'],
                'descripcion' => $method['descripcion'] ?? null,
                'icono' => $method['icono'] ?? null,
                'esta_activo' => $method['esta_activo'] ?? true,
                'comision_porcentaje' => $method['comision_porcentaje'] ?? 0,
                'orden' => $method['orden'] ?? (($index + 1) * 10),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('metodos_pago')->upsert(
            $payload,
            ['codigo'],
            ['nombre', 'descripcion', 'icono', 'esta_activo', 'comision_porcentaje', 'orden', 'updated_at']
        );
    }
}
