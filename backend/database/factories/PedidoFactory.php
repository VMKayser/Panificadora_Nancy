<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\User;
use App\Models\Cliente;
use App\Models\MetodoPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition()
    {
        return [
            'numero_pedido' => 'TEST-' . $this->faker->unique()->bothify('####'),
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'cliente_nombre' => $this->faker->firstName(),
            'cliente_apellido' => $this->faker->lastName(),
            'cliente_email' => $this->faker->safeEmail(),
            'cliente_telefono' => $this->faker->numerify('7########'),
            'tipo_entrega' => 'recojo_tienda',
            'subtotal' => 0,
            'descuento' => 0,
            'total' => 0,
            'metodos_pago_id' => MetodoPago::factory(),
            'estado' => 'pendiente',
            'estado_pago' => 'pendiente',
        ];
    }
}
