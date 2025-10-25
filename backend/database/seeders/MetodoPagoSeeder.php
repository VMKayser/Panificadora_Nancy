<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    // Use DB::upsert to make seeding atomic/idempotent (avoids race duplicate-key issues)
    $metodosPago = [
            [
                'nombre' => 'QR Simple BNB',
                'codigo' => 'qr_simple',
                'descripcion' => 'Pago mediante código QR del Banco Nacional de Bolivia',
                'icono' => null,
                'esta_activo' => true,
                'comision_porcentaje' => 0,
                'orden' => 1,
            ],
            [
                'nombre' => 'Transferencia Bancaria',
                'codigo' => 'transferencia',
                'descripcion' => 'Transferencia bancaria directa',
                'icono' => null,
                'esta_activo' => true,
                'comision_porcentaje' => 0,
                'orden' => 2,
            ],
            [
                'nombre' => 'Tarjeta de Crédito/Débito',
                'codigo' => 'tarjeta',
                'descripcion' => 'Pago con tarjeta de crédito o débito',
                'icono' => null,
                'esta_activo' => false, 
                'comision_porcentaje' => 3.5,
                'orden' => 3,
            ],
            [
                'nombre' => 'Pago en Efectivo',
                'codigo' => 'efectivo',
                'descripcion' => 'Pago en efectivo al momento de la entrega o recojo',
                'icono' => null,
                'esta_activo' => true,
                'comision_porcentaje' => 0,
                'orden' => 4,
            ],
        ];
        // Use a single upsert call to avoid the select-then-insert race that can lead to
        // duplicate-key errors when seeding is invoked repeatedly or concurrently.
        DB::table('metodos_pago')->upsert(
            $metodosPago,
            ['codigo'],
            ['nombre', 'descripcion', 'icono', 'esta_activo', 'comision_porcentaje', 'orden']
        );
    }
}
