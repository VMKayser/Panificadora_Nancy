<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('metodos_pago')) {
            return;
        }

        $now = now();
        $defaults = [
            [
                'codigo' => 'qr',
                'nombre' => 'Pago QR',
                'descripcion' => 'Escanea el código QR oficial y envía el comprobante.',
                'orden' => 10,
            ],
            [
                'codigo' => 'efectivo',
                'nombre' => 'Pago en efectivo',
                'descripcion' => 'Pago presencial en caja.',
                'orden' => 20,
            ],
        ];

        foreach ($defaults as $method) {
            DB::table('metodos_pago')->updateOrInsert(
                ['codigo' => $method['codigo']],
                [
                    'nombre' => $method['nombre'],
                    'descripcion' => $method['descripcion'],
                    'icono' => $method['icono'] ?? null,
                    'esta_activo' => true,
                    'comision_porcentaje' => $method['comision_porcentaje'] ?? 0,
                    'orden' => $method['orden'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('metodos_pago')) {
            return;
        }

        DB::table('metodos_pago')->whereIn('codigo', ['qr', 'efectivo'])->delete();
    }
};
