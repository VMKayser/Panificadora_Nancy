<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VendedorFactory extends Factory
{
    protected $model = \App\Models\Vendedor::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'codigo_vendedor' => 'VEN-' . fake()->numerify('####'),
            'comision_porcentaje' => 5.00,
            'comision_acumulada' => 0.00,
            'descuento_maximo_bs' => 0.00,
            'puede_dar_descuentos' => false,
            'puede_cancelar_ventas' => false,
            'turno' => 'mañana',
            'fecha_ingreso' => now()->subMonths(1),
            'estado' => 'activo',
            'observaciones' => null,
            'ventas_realizadas' => 0,
            'total_vendido' => 0.00,
            'descuentos_otorgados' => 0.00,
        ];
    }
}
