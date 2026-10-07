<?php

namespace Tests\Feature;

use App\Models\InventarioProductoFinal;
use App\Models\MateriaPrima;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Si una operación falla a la mitad no debe quedar nada guardado.
 *
 * RefreshDatabase normalmente envuelve cada test en una transacción, y
 * SafeTransaction no abre otra dentro, así que el rollback no se vería. Aquí se
 * desactiva esa transacción (como en producción) y la BD se recrea al terminar.
 */
class ReglasRollbackTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    /** Sin transacción envolvente: cada request maneja la suya, como en producción. */
    protected $connectionsToTransact = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Dejar la BD limpia para los demás tests (no hay transacción que revertir)
        $this->beforeApplicationDestroyed(fn () => $this->artisan('migrate:fresh'));
        $this->admin = User::factory()->create();
        Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));
    }

    public function test_si_falta_stock_de_ingredientes_no_queda_receta_ni_produccion()
    {
        $mp = MateriaPrima::factory()->create(['nombre' => 'Manteca', 'stock_actual' => 1, 'costo_unitario' => 3]);
        $producto = Producto::factory()->create();

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/inventario/producciones', [
            'producto_id' => $producto->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'unidades',
            'ingredientes' => [['materia_prima_id' => $mp->id, 'cantidad' => 2]],
        ])->assertStatus(422)->assertJsonStructure(['ingredientes_faltantes']);

        $this->assertSame(0, Receta::where('producto_id', $producto->id)->count());
        $this->assertSame(0, Produccion::count());
        $this->assertEqualsWithDelta(1.0, (float) $mp->fresh()->stock_actual, 0.001);
    }

    public function test_unidad_incompatible_con_la_receta_da_422_sin_guardar_nada()
    {
        $harina = MateriaPrima::factory()->create(['nombre' => 'Harina', 'stock_actual' => 100]);
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 10, 'unidad_rendimiento' => 'unidades']);
        $receta->ingredientes()->create(['materia_prima_id' => $harina->id, 'cantidad' => 10, 'unidad' => 'kg']);

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/inventario/producciones', [
            'producto_id' => $producto->id,
            'fecha_produccion' => now()->toDateString(),
            'cantidad_producida' => 10,
            'unidad' => 'kg',
        ])->assertStatus(422);

        $this->assertSame(0, Produccion::count());
        $this->assertEqualsWithDelta(100.0, (float) $harina->fresh()->stock_actual, 0.001);
    }

    public function test_el_mostrador_sin_stock_no_registra_la_venta()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 20]);
        $this->ensureInventory($producto->id, 1);
        $mp = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/pedidos', [
            'es_venta_mostrador' => true,
            'cliente_nombre' => 'Mostrador',
            'cliente_email' => 'pos@example.test',
            'metodos_pago_id' => $mp->id,
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertStatus(422)->assertJsonStructure(['insufficient_stock']);

        $this->assertSame(0, Pedido::count());
        $this->assertEqualsWithDelta(1.0, (float) InventarioProductoFinal::where('producto_id', $producto->id)->value('stock_actual'), 0.001);
    }
}
