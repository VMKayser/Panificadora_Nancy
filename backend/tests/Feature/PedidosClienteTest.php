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

    /**
     * Regla de negocio: los pedidos web no se limitan por el stock registrado
     * (se hornea a diario y por encargo) y no descuentan stock al crearse.
     */
    public function test_cliente_crea_pedido_publico_sin_limite_de_stock()
    {
    $producto = Producto::factory()->create(['precio_minorista' => 10]);
    $this->ensureInventory($producto->id, 1);
    $mp = \App\Models\MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        // Pide más de lo que hay en stock: se acepta igual
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
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Pedido creado exitosamente']);

        $this->assertDatabaseHas('pedidos', ['cliente_nombre' => 'Cliente Web']);
    // detalle_pedidos uses productos_id as the FK column
    $this->assertDatabaseHas('detalle_pedidos', ['productos_id' => $producto->id, 'cantidad' => 2]);

        // El stock no se toca hasta que el pedido se entregue
        $this->assertEqualsWithDelta(1.0, (float) InventarioProductoFinal::where('producto_id', $producto->id)->value('stock_actual'), 0.001);
    }
}
