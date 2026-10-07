<?php

namespace Database\Factories;

use App\Models\DetallePedido;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetallePedidoFactory extends Factory
{
    protected $model = DetallePedido::class;

    public function definition()
    {
        $producto = Producto::factory()->create();
        return [
            'pedidos_id' => null, // set by test when creating
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio' => $producto->precio_minorista ?? 10,
            'descuento' => 0,
        ];
    }
}
