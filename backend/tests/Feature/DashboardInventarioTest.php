<?php

namespace Tests\Feature;

use App\Models\DetallePedido;
use App\Models\MateriaPrima;
use App\Models\MetodoPago;
use App\Models\Panadero;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['name' => 'Admin']);
        Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pedido(Producto $producto, array $datos, int $cantidad = 1, bool $esExtra = false): Pedido
    {
        $mp = MetodoPago::firstOrCreate(['codigo' => 'qr'], ['nombre' => 'QR', 'esta_activo' => true, 'orden' => 1]);
        $creado = $datos['created_at'] ?? null;
        unset($datos['created_at']);
        $pedido = Pedido::create(array_merge([
            'numero_pedido' => 'T-' . uniqid(),
            'cliente_nombre' => 'Ana',
            'cliente_apellido' => 'Pérez',
            'cliente_email' => 'ana@example.test',
            'cliente_telefono' => '70000001',
            'tipo_entrega' => 'recoger',
            'subtotal' => 10 * $cantidad,
            'total' => 10 * $cantidad,
            'metodos_pago_id' => $mp->id,
            'estado' => 'confirmado',
            'estado_pago' => 'pagado',
        ], $datos));
        if ($creado) {
            $pedido->forceFill(['created_at' => $creado])->saveQuietly();
        }
        DetallePedido::create([
            'pedidos_id' => $pedido->id,
            'productos_id' => $producto->id,
            'nombre_producto' => $producto->nombre,
            'precio_unitario' => 10,
            'cantidad' => $cantidad,
            'subtotal' => 10 * $cantidad,
            'es_extra' => $esExtra,
        ]);
        return $pedido;
    }

    public function test_hoy_es_el_dia_de_bolivia_y_solo_cuenta_lo_pagado()
    {
        // 21:00 en Bolivia = 01:00 UTC del día siguiente
        Carbon::setTestNow(Carbon::parse('2026-03-10 01:00:00', 'UTC'));
        $producto = Producto::factory()->create(['nombre' => 'Pan']);

        $this->pedido($producto, ['created_at' => Carbon::parse('2026-03-09 23:30:00', 'UTC')]);                 // 19:30 BO, hoy
        $this->pedido($producto, ['created_at' => Carbon::parse('2026-03-09 12:00:00', 'UTC'), 'estado_pago' => 'pendiente']); // hoy, no pagado
        $this->pedido($producto, ['created_at' => Carbon::parse('2026-03-09 13:00:00', 'UTC'), 'estado' => 'cancelado']);     // cancelado
        $this->pedido($producto, ['created_at' => Carbon::parse('2026-03-08 23:00:00', 'UTC')]);                 // ayer en BO

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/inventario/dashboard')->assertStatus(200);

        $res->assertJsonFragment(['fecha' => '2026-03-09']);
        $this->assertSame(2, $res->json('pedidos_hoy'));
        $this->assertEquals(10.0, $res->json('ingresos_hoy'));
        $this->assertSame('2026-03-09', collect($res->json('ventas_por_temporada'))->last()['fecha']);
    }

    public function test_ventas_por_producto_excluyen_extras_y_ganancia_sin_costo()
    {
        $producto = Producto::factory()->create(['nombre' => 'Panetón']);
        $this->pedido($producto, [], 3);
        $this->pedido($producto, [], 2, true); // línea de extra del mismo producto
        $this->pedido($producto, ['estado_pago' => 'pendiente'], 5);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/inventario/dashboard')->assertStatus(200);

        $fila = collect($res->json('productos'))->firstWhere('id', $producto->id);
        $this->assertEquals(3.0, $fila['ventas']);
        $this->assertFalse($fila['costo_conocido']);
        $this->assertNull($fila['profit']);
    }

    public function test_el_ranking_es_por_panadero_y_no_por_quien_registra()
    {
        $panaderoUser = User::factory()->create(['name' => 'Don Julio']);
        $panadero = Panadero::factory()->create(['user_id' => $panaderoUser->id]);
        $harina = MateriaPrima::factory()->create(['nombre' => 'Harina', 'stock_actual' => 100]);
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 12]);
        $receta->ingredientes()->create(['materia_prima_id' => $harina->id, 'cantidad' => 1, 'unidad' => 'kg']);

        // El admin registra 2 docenas a nombre del panadero
        $this->actingAs($this->admin, 'sanctum')->postJson('/api/inventario/producciones', [
            'producto_id' => $producto->id,
            'panadero_id' => $panadero->id,
            'fecha_produccion' => now('America/La_Paz')->toDateString(),
            'cantidad_producida' => 2,
            'unidad' => 'docenas',
        ])->assertStatus(201);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/inventario/dashboard')->assertStatus(200);

        $this->assertEquals(24.0, $res->json('produccion_hoy'));
        $this->assertSame('Don Julio', $res->json('panaderos.0.nombre'));
        $this->assertEquals(24.0, $res->json('panaderos.0.produccion'));
    }
}
