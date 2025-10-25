<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Tests\Traits\InventorySetup;

class MovimientoEntradaProduccionTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_registra_entrada_produccion_y_actualiza_inventario()
    {
        $admin = \App\Models\User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);

        $producto = Producto::factory()->create(['precio_minorista' => 12]);
    $this->ensureInventory($producto->id, 5);

        $cantidadEntrada = 10;
        $nuevoStock = 5 + $cantidadEntrada;

        $payload = [
            'nuevo_stock' => $nuevoStock,
            'motivo' => 'produccion',
            'observaciones' => 'Prueba entrada produccion',
            'tipo_movimiento' => 'entrada_produccion'
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/inventario/productos/{$producto->id}/ajustar", $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Inventario ajustado exitosamente']);

        $mov = \App\Models\MovimientoProductoFinal::where('producto_id', $producto->id)->where('tipo_movimiento', 'entrada_produccion')->first();
        $this->assertNotNull($mov, 'No se creó movimiento de entrada_produccion');
        $this->assertEqualsWithDelta($cantidadEntrada, (float)$mov->cantidad, 0.001, 'Cantidad de entrada no coincide');

        $inv = InventarioProductoFinal::where('producto_id', $producto->id)->first();
        $this->assertEqualsWithDelta($nuevoStock, (float)$inv->stock_actual, 0.001, 'Stock después de entrada incorrecto');
    }
}
