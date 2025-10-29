<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\MateriaPrima;
use App\Models\InventarioProductoFinal;
use App\Models\User;

class ProduccionExtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_produccion_with_extra_ingredients_discounts_inventory_and_respects_harina()
    {
        // Create materias primas
        $harina = MateriaPrima::factory()->create(['nombre' => 'Harina', 'stock_actual' => 100.0, 'costo_unitario' => 1.0]);
        $azucar = MateriaPrima::factory()->create(['nombre' => 'Azúcar', 'stock_actual' => 50.0, 'costo_unitario' => 2.0]);

        // Create producto and receta
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10]);

        // Attach receta ingredientes (harina 10, azucar 5) - factories may differ; insert directly for clarity
        $receta->ingredientes()->create(['materia_prima_id' => $harina->id, 'cantidad' => 10, 'unidad' => 'kg']);
        $receta->ingredientes()->create(['materia_prima_id' => $azucar->id, 'cantidad' => 5, 'unidad' => 'kg']);

    // Ensure inventario for producto exists
    InventarioProductoFinal::query()->updateOrInsert(['producto_id' => $producto->id], ['stock_actual' => 0, 'costo_promedio' => 0]);

        // Prepare payload: produce 10 units (factor 1), but override harina_real_usada to 12 (more harina used)
        $payload = [
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'unidades',
            'harina_real_usada' => 12,
            'panadero_id' => null,
            // extra ingredient: add 2 units of azucar more
            'ingredientes' => [
                ['materia_prima_id' => $azucar->id, 'cantidad' => 2]
            ]
        ];

    // Authenticate and call endpoint (inventory routes are under /api/inventario and require auth)
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    // Call endpoint
    $res = $this->postJson('/api/inventario/producciones', $payload);
        $res->assertStatus(201);

        // Assert movimientos were created for this producción
        $produccionId = $res->json('data.id');
        $this->assertNotNull($produccionId, 'Response did not include production id');

        // Reload movimientos related to this producción
        $movs = \App\Models\MovimientoMateriaPrima::where('produccion_id', $produccionId)->get();

        // Expect a movimiento for harina with cantidad 12
        $harinaMov = $movs->firstWhere('cantidad', 12.0);
        $this->assertNotNull($harinaMov, 'No se encontró movimiento de harina por 12 unidades');

        // Expect movimientos for azucar: one with 5 and one with 2
        $azucarMov5 = $movs->firstWhere('cantidad', 5.0);
        $azucarMov2 = $movs->firstWhere('cantidad', 2.0);
        $this->assertNotNull($azucarMov5, 'No se encontró movimiento de azúcar por 5 unidades');
        $this->assertNotNull($azucarMov2, 'No se encontró movimiento de azúcar por 2 unidades');
    }
}
