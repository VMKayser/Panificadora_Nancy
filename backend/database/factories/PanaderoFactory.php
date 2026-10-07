<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PanaderoFactory extends Factory
{
    protected $model = \App\Models\Panadero::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'codigo_panadero' => 'PAN-' . fake()->numerify('####'),
            'direccion' => fake()->address(),
            'fecha_ingreso' => now()->subMonths(1),
            'turno' => 'mañana',
            'especialidad' => 'pan',
            'salario_base' => 0,
            'salario_por_kilo' => 0,
            'total_kilos_producidos' => 0,
            'total_unidades_producidas' => 0,
            'ultima_produccion' => null,
            'activo' => true,
            'observaciones' => null,
        ];
    }
}
