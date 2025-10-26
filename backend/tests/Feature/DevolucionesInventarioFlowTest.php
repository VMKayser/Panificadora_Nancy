<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\MateriaPrima;
use App\Models\InventarioProductoFinal;

class DevolucionesInventarioFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_sale_and_return_sequence_affects_inventories_correctly()
    {
        // Create materia prima (azucar)
        $azucar = MateriaPrima::factory()->create(['nombre' => 'Azúcar', 'stock_actual' => 20.0, 'costo_unitario' => 1.0]);

        // Create producto + receta that uses 5 units of azucar per 10 produced
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10]);
        $receta->ingredientes()->create(['materia_prima_id' => $azucar->id, 'cantidad' => 5, 'unidad' => 'kg']);

    // Ensure product inventory exists
    InventarioProductoFinal::query()->updateOrInsert(['producto_id' => $producto->id], ['stock_actual' => 0, 'costo_promedio' => 0]);

        // Authenticate user and produce 10 units -> consumes 5 azucar, adds 10 product
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $produccionPayload = [
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'unidades',
            'harina_real_usada' => 0.001,
            'ingredientes' => []
        ];

        $resProd = $this->postJson('/api/inventario/producciones', $produccionPayload);
        $resProd->assertStatus(201);

        $produccionId = $resProd->json('data.id');
        $this->assertNotNull($produccionId);

        // After production: azucar stock should be 15, product stock 10
        $azucar->refresh();
        $this->assertEquals(15.0, (float)$azucar->stock_actual, 'Azúcar stock incorrecto tras producción');

        $inventario = InventarioProductoFinal::where('producto_id', $producto->id)->first();
        $this->assertEquals(10.0, (float)$inventario->stock_actual, 'Stock producto incorrecto tras producción');

        // 2) Simulate sale of 3 units (salida_venta)
        $nuevoStockVenta = 10 - 3;
        $respVenta = $this->postJson("/api/inventario/productos/{$producto->id}/ajustar", [
            'nuevo_stock' => $nuevoStockVenta,
            'motivo' => 'venta de prueba',
            'observaciones' => 'Venta test',
            'tipo_movimiento' => 'salida_venta'
        ]);
        $respVenta->assertStatus(200);

        $inventario->refresh();
        $this->assertEquals(7.0, (float)$inventario->stock_actual, 'Stock producto incorrecto tras venta');

        // 3) Simulate return of 2 units (devolucion)
        $nuevoStockDevolucion = 7 + 2;
        $respDev = $this->postJson("/api/inventario/productos/{$producto->id}/ajustar", [
            'nuevo_stock' => $nuevoStockDevolucion,
            'motivo' => 'devolucion cliente',
            'observaciones' => 'Cliente devolvió 2 unidades'
        ]);
        $respDev->assertStatus(200);

        $inventario->refresh();
        $this->assertEquals(9.0, (float)$inventario->stock_actual, 'Stock producto incorrecto tras devolución');

        // Ensure materia prima stock (azucar) was not modified by sale/return
        $azucar->refresh();
        $this->assertEquals(15.0, (float)$azucar->stock_actual, 'Azúcar no debería cambiar tras venta/devolución de producto final');

        // Check movimientos: there should be entrada_produccion, salida_venta, and an ajuste (devolucion)
        $movs = \App\Models\MovimientoProductoFinal::where('producto_id', $producto->id)->get();
        $this->assertTrue($movs->contains('tipo_movimiento', 'entrada_produccion'));
        $this->assertTrue($movs->contains('tipo_movimiento', 'salida_venta'));
        $this->assertTrue($movs->contains('tipo_movimiento', 'ajuste'));
    }
}
