<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = \App\Models\Categoria::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'url' => fake()->unique()->slug(),
            'descripcion' => fake()->sentence(),
            'imagen' => null,
            'esta_activo' => true,
            'orden' => 0,
            'order' => 0,
        ];
    }
}
