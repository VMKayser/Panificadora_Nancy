<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Tests\Traits\InventorySetup;
use App\Models\User;

class MovimientoProductoFinalTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_movimiento_contiene_campos_esperados_y_stock_consistente()
    {
        // Crear usuario admin y asignar role
        $admin = User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
        $admin->roles()->attach($role->id);

        // Metodo de pago
    $mp = \App\Models\MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        // Producto y stock inicial
        $producto = Producto::factory()->create(['precio_minorista' => 10]);
    $this->ensureInventory($producto->id, 15);

        // Payload POS
        $payload = [
            'es_venta_mostrador' => true,
            'cliente_nombre' => 'Test Movimiento',
            'cliente_email' => 'mov@test.local',
            'cliente_telefono' => '70000001',
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 4, 'precio_unitario' => 10, 'subtotal' => 40],
            ],
            'subtotal' => 40,
            'total' => 40,
            'metodos_pago_id' => $mp->id,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/pedidos', $payload)
            ->assertStatus(201);

        $pedido = \App\Models\Pedido::where('cliente_email', 'mov@test.local')->first();
        $this->assertNotNull($pedido, 'Pedido no creado');

        // Obtener movimiento creado (puede ser creado por controller/observer o por el servicio)
        $movimiento = \App\Models\MovimientoProductoFinal::where('pedido_id', $pedido->id)->first();
        $this->assertNotNull($movimiento, 'Movimiento no encontrado');

        // Verificar campos obligatorios
        $this->assertNotNull($movimiento->producto_id);
        $this->assertNotNull($movimiento->tipo_movimiento);
        $this->assertNotNull($movimiento->cantidad);
        $this->assertNotNull($movimiento->stock_anterior);
        $this->assertNotNull($movimiento->stock_nuevo);
        $this->assertNotNull($movimiento->observaciones);

        // Verificar consistencia: stock_anterior - stock_nuevo == cantidad
        $anterior = (float) $movimiento->stock_anterior;
        $nuevo = (float) $movimiento->stock_nuevo;
        $cantidad = (float) $movimiento->cantidad;

        $this->assertEqualsWithDelta($anterior - $nuevo, $cantidad, 0.001, 'La resta stock_anterior - stock_nuevo no coincide con cantidad');

        // Verificar que el inventario refleja el nuevo stock
        $inventario = InventarioProductoFinal::where('producto_id', $producto->id)->first();
        $this->assertEqualsWithDelta($nuevo, (float) $inventario->stock_actual, 0.001, 'Inventario no refleja stock_nuevo');
    }
}
