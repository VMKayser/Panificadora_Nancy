<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Temporada2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Temporada 2026 (pedido del 2026-10-02): precio por confirmar, fecha límite
 * de pedidos y el dato que el producto pide al cliente (nombre del difunto,
 * "Para:" de la cajita).
 */
class ReglasTemporadaTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pedidoWeb(array $productos)
    {
        $metodo = MetodoPago::firstOrCreate(['codigo' => 'qr'], ['nombre' => 'QR', 'esta_activo' => true, 'orden' => 1]);

        return $this->postJson('/api/pedidos', [
            'cliente_nombre' => 'Rosa Mamani',
            'cliente_telefono' => '71234567',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $metodo->id,
            'productos' => $productos,
        ]);
    }

    private function producto(array $datos = []): Producto
    {
        $producto = Producto::factory()->create(array_merge(['precio_minorista' => 100], $datos));
        $this->ensureInventory($producto->id, 10);

        return $producto;
    }

    public function test_con_precio_por_confirmar_no_se_vende_por_la_web()
    {
        $producto = $this->producto(['precio_por_confirmar' => true]);

        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "El precio de {$producto->nombre} está por confirmar. Consúltalo por WhatsApp."]);

        $this->assertSame(0, Pedido::count());
    }

    public function test_los_pedidos_cierran_al_terminar_el_dia_en_hora_de_bolivia()
    {
        $producto = $this->producto(['pedidos_hasta' => '2026-10-24']);

        // 24/10 a las 23:30 en Bolivia (UTC-4): todavía se puede
        Carbon::setTestNow(Carbon::parse('2026-10-25 03:30:00', 'UTC'));
        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);

        // 25/10 a las 00:30 en Bolivia: cerrado
        Carbon::setTestNow(Carbon::parse('2026-10-25 04:30:00', 'UTC'));
        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "Los pedidos de {$producto->nombre} se cerraron el 24/10/2026."]);

        $this->assertSame(1, Pedido::count());
    }

    public function test_exige_el_dato_personalizado_y_lo_guarda_con_su_etiqueta()
    {
        $producto = $this->producto(['etiqueta_personalizacion' => 'Nombre del difunto']);

        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['productos.0.personalizacion']);

        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1, 'personalizacion' => '  Juan Pérez ']])
            ->assertStatus(201)
            ->assertJsonPath('pedido.detalles.0.personalizacion', 'Nombre del difunto: Juan Pérez');
    }

    public function test_los_extras_no_piden_el_dato_personalizado()
    {
        $producto = $this->producto([
            'etiqueta_personalizacion' => 'Nombre del difunto',
            'tiene_extras' => true,
            'extras_disponibles' => [['nombre' => 'Donitas (docena)', 'precio' => 30, 'descripcion' => '12 unidades · Bs 2,50 c/u']],
        ]);

        $this->pedidoWeb([
            ['id' => $producto->id, 'cantidad' => 1, 'personalizacion' => 'Ana Quispe'],
            ['id' => $producto->id, 'cantidad' => 2, 'extra_index' => 0],
        ])->assertStatus(201);

        $pedido = Pedido::first();
        $this->assertEquals(160, $pedido->total);
        $extra = DetallePedido::where('es_extra', true)->first();
        $this->assertNull($extra->personalizacion);
        $this->assertEquals(60, $extra->subtotal);
    }

    public function test_sin_etiqueta_el_texto_se_guarda_tal_cual_y_es_opcional()
    {
        $producto = $this->producto();

        $this->pedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $this->assertNull(DetallePedido::first()->personalizacion);
    }

    public function test_el_panel_guarda_y_vacia_los_campos_de_temporada()
    {
        $admin = User::factory()->create();
        Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));
        $producto = $this->producto();

        $this->actingAs($admin, 'sanctum')->putJson("/api/admin/productos/{$producto->id}", [
            'precio_por_confirmar' => true,
            'pedidos_hasta' => '2026-10-24',
            'etiqueta_personalizacion' => 'Para',
        ])->assertStatus(200);

        $producto->refresh();
        $this->assertTrue($producto->precio_por_confirmar);
        $this->assertSame('2026-10-24', $producto->pedidos_hasta->toDateString());
        $this->assertSame('Para', $producto->etiqueta_personalizacion);

        $this->actingAs($admin, 'sanctum')->putJson("/api/admin/productos/{$producto->id}", [
            'precio_por_confirmar' => false,
            'pedidos_hasta' => null,
            'etiqueta_personalizacion' => '',
        ])->assertStatus(200);

        $producto->refresh();
        $this->assertFalse($producto->precio_por_confirmar);
        $this->assertNull($producto->pedidos_hasta);
        $this->assertNull($producto->etiqueta_personalizacion);
    }

    public function test_el_seeder_de_temporada_se_puede_correr_dos_veces()
    {
        Storage::fake('public');
        $navidad = Categoria::factory()->create(['nombre' => 'Navidad', 'url' => 'navidad']);
        foreach (range(1, 9) as $i) {
            $this->producto(['categorias_id' => $navidad->id, 'nombre' => "Panetón {$i}"]);
        }

        $this->seed(Temporada2026Seeder::class);
        $this->seed(Temporada2026Seeder::class);

        $paneton = Producto::find(1);
        $this->assertSame('Panetón tradicional en caja + juguete (700 g)', $paneton->nombre);
        $this->assertTrue($paneton->precio_por_confirmar);

        $todosSantos = Categoria::where('url', 'todos-santos')->firstOrFail();
        $this->assertSame(16, Producto::where('categorias_id', $todosSantos->id)->count());

        $mesa = Producto::where('url', 'mesa-completa-para-difuntos')->firstOrFail();
        $this->assertEquals(3000, $mesa->precio_minorista);
        $this->assertSame('Nombre del difunto', $mesa->etiqueta_personalizacion);
        $this->assertSame('2026-10-24', $mesa->pedidos_hasta->toDateString());
        $this->assertCount(6, $mesa->extras_disponibles);

        // Las fotos (si están en storage/app/temporada-2026) no se repiten
        foreach (Producto::with('imagenes')->get() as $producto) {
            $alts = $producto->imagenes->pluck('texto_alternativo')->filter();
            $this->assertSame($alts->count(), $alts->unique()->count(), "Fotos repetidas en {$producto->nombre}");
        }
    }
}
