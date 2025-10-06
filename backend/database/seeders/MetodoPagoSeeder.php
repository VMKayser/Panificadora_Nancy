<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MetodoPago;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MetodoPago::query()->delete();

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

        foreach ($metodosPago as $metodo) {
            MetodoPago::create($metodo);
        }
    }
}
