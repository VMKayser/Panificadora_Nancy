<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('7########'),
            'direccion' => fake()->address(),
            'ci' => fake()->unique()->numerify('########'),
            'tipo_cliente' => fake()->randomElement(['particular', 'empresa']),
            'total_pedidos' => 0,
            'total_gastado' => 0.00,
            'fecha_ultimo_pedido' => null,
            'activo' => true,
            'notas' => null,
        ];
    }

    /**
     * Indicate that the cliente is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indicate that the cliente is a business client.
     */
    public function empresa(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_cliente' => 'empresa',
        ]);
    }

    /**
     * Indicate that the cliente is a regular client.
     */
    public function particular(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_cliente' => 'particular',
        ]);
    }
}
