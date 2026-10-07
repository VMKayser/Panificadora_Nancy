<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Producto::class;

    public function definition(): array
    {
        return [
            'categorias_id' => \App\Models\Categoria::factory(),
            'nombre' => fake()->word() . ' ' . fake()->word(),
            'url' => fake()->unique()->slug(),
            'descripcion' => fake()->sentence(),
            'descripcion_corta' => fake()->sentence(3),
            'unidad_medida' => fake()->randomElement(['unidad','cm','docena','paquete','gramos','kilogramos','arroba','porcion']),
            'presentacion' => null,
            'tiene_variantes' => false,
            'tiene_extras' => false,
            'extras_disponibles' => [],
            'precio_minorista' => fake()->randomFloat(2, 1, 100),
            'precio_mayorista' => fake()->randomFloat(2, 1, 90),
            'cantidad_minima_mayoreo' => 0,
            'es_de_temporada' => false,
            'esta_activo' => true,
            'permite_delivery' => false,
            'permite_envio_nacional' => false,
            'requiere_tiempo_anticipacion' => false,
            'tiempo_anticipacion' => 0,
            'unidad_tiempo' => null,
            'limite_produccion' => false,
        ];
    }
}
