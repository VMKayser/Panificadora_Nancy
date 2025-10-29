<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Tests\Traits\InventorySetup;

class MovimientoMermaTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_crea_movimiento_merma_y_actualiza_inventario()
    {
        $admin = \App\Models\User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);

        $producto = Producto::factory()->create(['precio_minorista' => 3]);
    $this->ensureInventory($producto->id, 20);

        // Simular una merma: llamar al endpoint de ajuste para crear una salida_merma
        $cantidadMerma = 5;
        $nuevoStock = 20 - $cantidadMerma;

        $payload = [
            'nuevo_stock' => $nuevoStock,
            'motivo' => 'merma',
            'observaciones' => 'Prueba merma',
            'tipo_movimiento' => 'salida_merma'
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/inventario/productos/{$producto->id}/ajustar", $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Inventario ajustado exitosamente']);

        // verificar movimiento y stock
        $mov = \App\Models\MovimientoProductoFinal::where('producto_id', $producto->id)->where('tipo_movimiento', 'salida_merma')->first();
        $this->assertNotNull($mov, 'No se creó movimiento de merma');
        $this->assertEqualsWithDelta($cantidadMerma, (float)$mov->cantidad, 0.001);

        $inv = InventarioProductoFinal::where('producto_id', $producto->id)->first();
        $this->assertEqualsWithDelta($nuevoStock, (float)$inv->stock_actual, 0.001, 'Stock después de merma incorrecto');
    }
}
