<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MetodoPagoFactory extends Factory
{
    protected $model = \App\Models\MetodoPago::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Pago QR',
            'codigo' => 'qr-' . $this->faker->unique()->numerify('####'),
            'esta_activo' => true,
            'comision_porcentaje' => 0,
            'orden' => 1,
        ];
    }
}
