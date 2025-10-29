<?php

namespace Database\Factories;

use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoCajaFactory extends Factory
{
    protected $model = MovimientoCaja::class;

    public function definition()
    {
        return [
            'tipo' => $this->faker->randomElement(['ingreso', 'salida']),
            'monto' => $this->faker->randomFloat(2, 1, 1000),
            'descripcion' => $this->faker->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
