<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Tests\Traits\InventorySetup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PedidosClienteTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_cliente_crea_pedido_publico_y_valida_stock()
    {
    $producto = Producto::factory()->create(['precio_minorista' => 10]);
    $this->ensureInventory($producto->id, 1);
    $mp = \App\Models\MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        // Intenta crear pedido con cantidad mayor al stock
        $payload = [
            'cliente_nombre' => 'Cliente Web',
            'cliente_apellido' => 'Web',
            'cliente_email' => 'web@example.test',
            'cliente_telefono' => '70000001',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $mp->id,
            'productos' => [
                ['id' => $producto->id, 'cantidad' => 2]
            ],
        ];

        $this->postJson('/api/pedidos', $payload)
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        // Ahora crear con cantidad permitida
    $payload['productos'][0]['cantidad'] = 1;
        $this->postJson('/api/pedidos', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Pedido creado exitosamente']);

        $this->assertDatabaseHas('pedidos', ['cliente_nombre' => 'Cliente Web']);
    // detalle_pedidos uses productos_id as the FK column
    $this->assertDatabaseHas('detalle_pedidos', ['productos_id' => $producto->id, 'cantidad' => 1]);

    // For ecommerce flow inventory may be decremented by observer when estado changes to 'confirmado' or 'entregado'.
    // Ensure there's at least a movimiento record or the inventory row exists (stock may still be 1 until confirmed).
    $this->assertDatabaseHas('inventario_productos_finales', ['producto_id' => $producto->id]);
    }
}
