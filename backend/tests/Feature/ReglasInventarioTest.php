<?php

namespace Tests\Feature;

use App\Models\InventarioProductoFinal;
use App\Models\MateriaPrima;
use App\Models\MovimientoProductoFinal;
use App\Models\Panadero;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Reglas de inventario y producción acordadas el 2026-09-30.
 */
class ReglasInventarioTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));
    }

    private function stock(int $productoId): float
    {
        return (float) InventarioProductoFinal::where('producto_id', $productoId)->value('stock_actual');
    }

    /** Producto con receta: 10 kg de harina y 2 kg de azúcar rinden 10 unidades. */
    private function productoConReceta(): array
    {
        $harina = MateriaPrima::factory()->create(['nombre' => 'Harina de trigo', 'stock_actual' => 100, 'costo_unitario' => 1]);
        $azucar = MateriaPrima::factory()->create(['nombre' => 'Azúcar', 'stock_actual' => 50, 'costo_unitario' => 2]);
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10, 'unidad_rendimiento' => 'unidades']);
        $receta->ingredientes()->create(['materia_prima_id' => $harina->id, 'cantidad' => 10, 'unidad' => 'kg']);
        $receta->ingredientes()->create(['materia_prima_id' => $azucar->id, 'cantidad' => 2, 'unidad' => 'kg']);

        return [$producto, $receta, $harina, $azucar];
    }

    private function producir(Producto $producto, array $extra = [])
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson('/api/inventario/producciones', array_merge([
            'producto_id' => $producto->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'unidades',
        ], $extra));
    }

    public function test_producir_suma_al_stock_existente_sin_reiniciarlo()
    {
        [$producto] = $this->productoConReceta();
        InventarioProductoFinal::where('producto_id', $producto->id)->update(['stock_actual' => 70, 'stock_minimo' => 15]);

        $this->producir($producto, ['cantidad_producida' => 50])->assertStatus(201);

        $this->assertEqualsWithDelta(120.0, $this->stock($producto->id), 0.001);
        $this->assertEqualsWithDelta(15.0, (float) InventarioProductoFinal::where('producto_id', $producto->id)->value('stock_minimo'), 0.001);
    }

    public function test_sin_harina_real_se_usa_la_teorica_y_cuenta_para_el_panadero()
    {
        [$producto, , $harina] = $this->productoConReceta();
        $panadero = Panadero::factory()->create(['user_id' => User::factory()->create()->id]);

        $res = $this->producir($producto, [
            'cantidad_producida' => 20,
            'harina_real_usada' => 0,
            'panadero_id' => $panadero->id,
        ])->assertStatus(201);

        $this->assertEqualsWithDelta(80.0, (float) $harina->fresh()->stock_actual, 0.001);
        $produccion = Produccion::find($res->json('data.id'));
        $this->assertEqualsWithDelta(20.0, (float) $produccion->harina_real_usada, 0.001);
        $this->assertSame('normal', $produccion->tipo_diferencia);
        $this->assertEqualsWithDelta(20.0, (float) $panadero->fresh()->total_kilos_producidos, 0.001);
    }

    public function test_con_harina_real_se_descuenta_esa_y_se_registra_la_diferencia()
    {
        [$producto, , $harina] = $this->productoConReceta();

        $res = $this->producir($producto, ['harina_real_usada' => 12])->assertStatus(201);

        $this->assertEqualsWithDelta(88.0, (float) $harina->fresh()->stock_actual, 0.001);
        $produccion = Produccion::find($res->json('data.id'));
        $this->assertEqualsWithDelta(2.0, (float) $produccion->diferencia_harina, 0.001);
        $this->assertSame('exceso', $produccion->tipo_diferencia);
    }

    public function test_los_ingredientes_manuales_no_sobrescriben_la_receta()
    {
        [$producto, $receta, $harina, $azucar] = $this->productoConReceta();

        $this->producir($producto, [
            'ingredientes' => [['materia_prima_id' => $azucar->id, 'cantidad' => 1]],
        ])->assertStatus(201);

        $ingredientes = $receta->fresh()->ingredientes;
        $this->assertCount(2, $ingredientes);
        $this->assertEqualsWithDelta(2.0, (float) $ingredientes->firstWhere('materia_prima_id', $azucar->id)->cantidad, 0.001);
        // Receta (2) + extra de esta producción (1)
        $this->assertEqualsWithDelta(47.0, (float) $azucar->fresh()->stock_actual, 0.001);
    }

    public function test_sin_receta_se_crea_con_los_ingredientes_y_no_se_descuentan_dos_veces()
    {
        $mp = MateriaPrima::factory()->create(['nombre' => 'Manteca', 'stock_actual' => 10, 'costo_unitario' => 3]);
        $producto = Producto::factory()->create();

        $this->producir($producto, [
            'ingredientes' => [['materia_prima_id' => $mp->id, 'cantidad' => 2]],
        ])->assertStatus(201)->assertJsonFragment(['receta_info' => 'Receta creada automáticamente']);

        $this->assertSame(1, Receta::where('producto_id', $producto->id)->count());
        $this->assertEqualsWithDelta(8.0, (float) $mp->fresh()->stock_actual, 0.001);
    }

    public function test_convierte_gramos_de_la_receta_a_kg_del_stock()
    {
        $sal = MateriaPrima::factory()->create(['nombre' => 'Sal', 'unidad_medida' => 'kg', 'stock_actual' => 1, 'costo_unitario' => 4]);
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10]);
        $receta->ingredientes()->create(['materia_prima_id' => $sal->id, 'cantidad' => 20, 'unidad' => 'g']);

        $this->producir($producto)->assertStatus(201);

        $this->assertEqualsWithDelta(0.98, (float) $sal->fresh()->stock_actual, 0.0001);
    }

    public function test_las_docenas_se_suman_como_unidades()
    {
        [$producto, , $harina] = $this->productoConReceta();

        $this->producir($producto, ['cantidad_producida' => 2, 'unidad' => 'docenas'])->assertStatus(201);

        $this->assertEqualsWithDelta(24.0, $this->stock($producto->id), 0.001);
        // 24 unidades = 2,4 veces la receta de 10 => 24 kg de harina
        $this->assertEqualsWithDelta(76.0, (float) $harina->fresh()->stock_actual, 0.001);
    }

    public function test_cancelar_una_produccion_revierte_el_inventario()
    {
        [$producto, , $harina, $azucar] = $this->productoConReceta();
        $res = $this->producir($producto)->assertStatus(201);
        $id = $res->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/inventario/producciones/{$id}/cancelar", ['motivo' => 'Registrada por error'])
            ->assertStatus(200);

        $this->assertSame('cancelado', Produccion::find($id)->estado);
        $this->assertEqualsWithDelta(0.0, $this->stock($producto->id), 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $harina->fresh()->stock_actual, 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $azucar->fresh()->stock_actual, 0.001);
    }

    public function test_no_se_cancela_una_produccion_ya_vendida()
    {
        [$producto] = $this->productoConReceta();
        $id = $this->producir($producto)->assertStatus(201)->json('data.id');
        InventarioProductoFinal::where('producto_id', $producto->id)->update(['stock_actual' => 3]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/inventario/producciones/{$id}/cancelar", ['motivo' => 'x'])
            ->assertStatus(422);
        $this->assertSame('completado', Produccion::find($id)->estado);
    }

    public function test_la_merma_por_diferencia_se_registra_como_salida_merma_y_sale_en_el_reporte()
    {
        $producto = Producto::factory()->create();
        $this->ensureInventory($producto->id, 10);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/inventario/productos/{$producto->id}/ajustar", [
                'cantidad' => 3,
                'direccion' => 'salida',
                'motivo' => 'merma',
            ])->assertStatus(200);

        $this->assertEqualsWithDelta(7.0, $this->stock($producto->id), 0.001);
        $this->assertDatabaseHas('movimientos_productos_finales', [
            'producto_id' => $producto->id,
            'tipo_movimiento' => 'salida_merma',
        ]);

        // El reporte incluye el último día del rango (hoy)
        $hoy = now()->toDateString();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/inventario/reporte-mermas?fecha_desde={$hoy}&fecha_hasta={$hoy}")
            ->assertStatus(200)
            ->assertJsonFragment(['total_mermas' => 3.0]);
    }

    public function test_un_ajuste_no_puede_dejar_stock_negativo()
    {
        $producto = Producto::factory()->create();
        $this->ensureInventory($producto->id, 2);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/inventario/productos/{$producto->id}/ajustar", [
                'cantidad' => 5,
                'direccion' => 'salida',
                'motivo' => 'correccion',
            ])->assertStatus(422);
        $this->assertEqualsWithDelta(2.0, $this->stock($producto->id), 0.001);
    }

    public function test_la_salida_de_materia_prima_se_aplica_sobre_el_stock_actual()
    {
        $mp = MateriaPrima::factory()->create(['nombre' => 'Levadura', 'stock_actual' => 5]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/inventario/materias-primas/{$mp->id}/ajuste", [
                'cantidad' => 1.5,
                'direccion' => 'salida',
                'motivo' => 'merma',
            ])->assertStatus(200);

        $this->assertEqualsWithDelta(3.5, (float) $mp->fresh()->stock_actual, 0.001);
        $this->assertDatabaseHas('movimientos_materia_prima', ['materia_prima_id' => $mp->id, 'tipo_movimiento' => 'salida_merma']);
    }

    public function test_editar_o_reactivar_un_producto_no_toca_su_stock()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->ensureInventory($producto->id, 42);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/productos/{$producto->id}", ['nombre' => 'Pan de batalla', 'cantidad' => 1])
            ->assertStatus(200);
        $this->assertEqualsWithDelta(42.0, $this->stock($producto->id), 0.001);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/productos/{$producto->id}/toggle-active")->assertStatus(200);
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/productos/{$producto->id}/toggle-active")->assertStatus(200);
        $this->assertEqualsWithDelta(42.0, $this->stock($producto->id), 0.001);
    }

    public function test_un_producto_nuevo_empieza_con_costo_cero()
    {
        $categoria = \App\Models\Categoria::factory()->create();

        $res = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/productos', [
            'categorias_id' => $categoria->id,
            'nombre' => 'Marraqueta',
            'precio_minorista' => 1,
        ])->assertStatus(201);

        $inventario = InventarioProductoFinal::where('producto_id', $res->json('producto.id'))->first();
        $this->assertEqualsWithDelta(0.0, (float) $inventario->stock_actual, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $inventario->costo_promedio, 0.001);
    }
}
