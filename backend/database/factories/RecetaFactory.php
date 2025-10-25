<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receta>
 */
class RecetaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Receta::class;

    public function definition(): array
    {
        return [
            'producto_id' => null,
            'nombre_receta' => fake()->word() . ' receta',
            'descripcion' => fake()->sentence(),
            'rendimiento' => 10,
            'unidad_rendimiento' => 'unidades',
            'costo_total_calculado' => 0,
            'costo_unitario_calculado' => 0,
            'activa' => true,
            'version' => 1,
        ];
    }
}
