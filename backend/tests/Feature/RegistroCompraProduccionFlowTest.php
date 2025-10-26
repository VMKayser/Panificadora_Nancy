<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\InventarioProductoFinal;
use App\Models\User;

class RegistroCompraProduccionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_compra_y_luego_produccion_asignada_a_panadero()
    {
        // 1) Crear usuario que registra la compra
        $comprador = User::factory()->create();
        $this->actingAs($comprador, 'sanctum');

        // 2) Crear materia prima con stock inicial 0
        $mp = MateriaPrima::factory()->create([
            'nombre' => 'Azúcar',
            'stock_actual' => 0.0,
            'costo_unitario' => 10.0
        ]);

        // 3) Registrar compra vía API
        $compraPayload = [
            'cantidad' => 20,
            'costo_unitario' => 12.5,
            'numero_factura' => 'FAC-1001',
            'observaciones' => 'Compra de prueba'
        ];

        $resCompra = $this->postJson("/api/inventario/materias-primas/{$mp->id}/compra", $compraPayload);
        $resCompra->assertStatus(200);

        // Refresh and assert stock updated
        $mp->refresh();
        $this->assertEquals(20.0, (float)$mp->stock_actual, 'El stock no se actualizó tras la compra');

        // Assert movimiento creado
        $mov = \App\Models\MovimientoMateriaPrima::where('materia_prima_id', $mp->id)
            ->where('tipo_movimiento', 'entrada_compra')
            ->where('cantidad', 20)
            ->first();
        $this->assertNotNull($mov, 'No se creó el movimiento de entrada de compra');
        $this->assertEquals($comprador->id, $mov->user_id, 'El movimiento no registra el user_id correcto');

        // 4) Crear producto y receta que consuma esa materia prima
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10]);
        $receta->ingredientes()->create(['materia_prima_id' => $mp->id, 'cantidad' => 5, 'unidad' => 'kg']);

    InventarioProductoFinal::query()->updateOrInsert(['producto_id' => $producto->id], ['stock_actual' => 0, 'costo_promedio' => 0]);

        // 5) Crear panadero y actuar como él para registrar la producción
        $panadero = User::factory()->create();
        $this->actingAs($panadero, 'sanctum');

        $produccionPayload = [
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'unidades',
            'harina_real_usada' => 0.001, // minimal positive value to pass validation
            'ingredientes' => []
        ];

        $resProd = $this->postJson('/api/inventario/producciones', $produccionPayload);
        $resProd->assertStatus(201);

        $produccionId = $resProd->json('data.id');
        $this->assertNotNull($produccionId, 'La respuesta no devolvió id de producción');

        // Assert la producción quedó asignada al panadero
        $this->assertEquals($panadero->id, $resProd->json('data.user_id'));

        // Assert movimiento de salida por la receta fue creado
        $movSalida = \App\Models\MovimientoMateriaPrima::where('produccion_id', $produccionId)
            ->where('materia_prima_id', $mp->id)
            ->where('tipo_movimiento', 'salida_produccion')
            ->first();
        $this->assertNotNull($movSalida, 'No se creó movimiento de salida para la producción');

        // Stock final debe ser 15 (20 - 5)
        $mp->refresh();
        $this->assertEquals(15.0, (float)$mp->stock_actual, 'El stock final no es correcto tras la producción');
    }
}
