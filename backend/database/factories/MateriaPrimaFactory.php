<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MateriaPrima>
 */
class MateriaPrimaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\MateriaPrima::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'codigo_interno' => fake()->unique()->bothify('MP-###'),
            // Fijo en kg: con unidades aleatorias las recetas de los tests (en kg)
            // se convertían o fallaban según la suerte.
            'unidad_medida' => 'kg',
            'stock_actual' => fake()->randomFloat(3, 0, 200),
            'stock_minimo' => fake()->randomFloat(3, 0, 20),
            'costo_unitario' => fake()->randomFloat(2, 0, 500),
            'proveedor' => fake()->company(),
            'ultima_compra' => now(),
            'activo' => true,
        ];
    }
}
