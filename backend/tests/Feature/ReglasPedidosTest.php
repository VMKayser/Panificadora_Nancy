<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\InventarioProductoFinal;
use App\Models\MetodoPago;
use App\Models\MovimientoProductoFinal;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use App\Services\InventarioService;
use App\Support\HoraNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Reglas de negocio de pedidos acordadas el 2026-09-30.
 */
class ReglasPedidosTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    private function usuarioConRol(string $rol): User
    {
        $user = User::factory()->create();
        Role::query()->updateOrInsert(['name' => $rol], ['description' => ucfirst($rol)]);
        $user->roles()->attach(Role::where('name', $rol)->value('id'));
        return $user;
    }

    private function metodoPago(): MetodoPago
    {
        return MetodoPago::firstOrCreate(['codigo' => 'qr'], ['nombre' => 'QR', 'esta_activo' => true, 'orden' => 1]);
    }

    private function crearPedidoWeb(array $productos, array $extra = [])
    {
        return $this->postJson('/api/pedidos', array_merge([
            'cliente_nombre' => 'Ana',
            'cliente_apellido' => 'Pérez',
            'cliente_email' => 'ana@example.test',
            'cliente_telefono' => '70000001',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $this->metodoPago()->id,
            'productos' => $productos,
        ], $extra));
    }

    private function stock(int $productoId): float
    {
        return (float) InventarioProductoFinal::where('producto_id', $productoId)->value('stock_actual');
    }

    public function test_el_stock_se_descuenta_al_entregar_y_no_al_confirmar()
    {
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->ensureInventory($producto->id, 10);

        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 3]])->assertStatus(201);
        $pedido = Pedido::first();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200);
        $this->assertEqualsWithDelta(10.0, $this->stock($producto->id), 0.001);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'entregado'])
            ->assertStatus(200);
        $this->assertEqualsWithDelta(7.0, $this->stock($producto->id), 0.001);
        $this->assertTrue((bool) $pedido->fresh()->stock_descargado);
        $this->assertDatabaseHas('movimientos_productos_finales', [
            'pedido_id' => $pedido->id,
            'tipo_movimiento' => 'salida_venta',
        ]);
    }

    public function test_los_estados_solo_avanzan_y_los_finales_no_cambian()
    {
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $pedido = Pedido::first();

        // Saltar pasos hacia adelante está permitido
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'listo'])
            ->assertStatus(200);

        // Retroceder no
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(422);

        // Un estado inexistente da 422, no 500
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'en_camino'])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'entregado'])
            ->assertStatus(200);

        // Entregado es final: no se puede cancelar
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/pedidos/{$pedido->id}/cancelar", ['motivo_cancelacion' => 'x'])
            ->assertStatus(422);
        $this->assertSame('entregado', $pedido->fresh()->estado);
    }

    public function test_cancelar_un_pedido_con_stock_descontado_lo_repone()
    {
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->ensureInventory($producto->id, 10);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 4]])->assertStatus(201);
        $pedido = Pedido::first();

        // Pedido antiguo: con la regla anterior el stock se descontaba al confirmar
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200);
        (new InventarioService())->descontarInventario($pedido->fresh());
        $this->assertEqualsWithDelta(6.0, $this->stock($producto->id), 0.001);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/pedidos/{$pedido->id}/cancelar", ['motivo_cancelacion' => 'Cliente no vino'])
            ->assertStatus(200);

        $this->assertEqualsWithDelta(10.0, $this->stock($producto->id), 0.001);
        $this->assertFalse((bool) $pedido->fresh()->stock_descargado);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'cancelado', 'notas_cancelacion' => 'Cliente no vino']);
    }

    public function test_marcar_pagado_y_los_ingresos_solo_cuentan_pagados()
    {
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create(['precio_minorista' => 10]);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 3]])->assertStatus(201);
        [$pagado, $noPagado] = Pedido::orderBy('id')->get()->all();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pagado->id}/pago", ['estado_pago' => 'pagado'])
            ->assertStatus(200);
        $this->assertSame('pagado', $pagado->fresh()->estado_pago);
        $this->assertNotNull($pagado->fresh()->fecha_pago);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/pedidos/stats')
            ->assertStatus(200)
            ->assertJsonFragment(['ingresos_totales' => 10.0, 'pedidos_pagados' => 1]);

        // Desmarcar
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pagado->id}/pago", ['estado_pago' => 'pendiente'])
            ->assertStatus(200);
        $this->assertNull($pagado->fresh()->fecha_pago);
    }

    public function test_la_fecha_de_entrega_conserva_la_hora_local()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $entrega = HoraNegocio::ahoraLocal()->addDays(2)->setTime(15, 30);

        $res = $this->crearPedidoWeb(
            [['id' => $producto->id, 'cantidad' => 1]],
            ['entrega_datetime' => $entrega->format('Y-m-d H:i:s')]
        )->assertStatus(201);

        // Sin "Z": el navegador la interpreta como hora local y no muestra el día anterior
        $this->assertSame($entrega->format('Y-m-d\TH:i:s'), $res->json('pedido.fecha_entrega'));
        $this->assertSame('15:30:00', Pedido::first()->hora_entrega);
    }

    public function test_rechaza_entregas_en_el_pasado_o_antes_de_la_anticipacion()
    {
        $torta = Producto::factory()->create([
            'precio_minorista' => 80,
            'requiere_tiempo_anticipacion' => true,
            'tiempo_anticipacion' => 24,
            'unidad_tiempo' => 'horas',
        ]);
        $ahora = HoraNegocio::ahoraLocal();

        $this->crearPedidoWeb([['id' => $torta->id, 'cantidad' => 1]], [
            'entrega_datetime' => $ahora->copy()->subDay()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('entrega_datetime');

        $this->crearPedidoWeb([['id' => $torta->id, 'cantidad' => 1]], [
            'entrega_datetime' => $ahora->copy()->addHours(3)->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('entrega_datetime');

        $this->crearPedidoWeb([['id' => $torta->id, 'cantidad' => 1]], [
            'entrega_datetime' => $ahora->copy()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertStatus(201);
    }

    public function test_el_numero_de_pedido_no_se_repite_aunque_se_borren_pedidos()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        Pedido::orderByDesc('id')->first()->delete();

        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);

        $anio = now(HoraNegocio::zona())->format('Y');
        $numeros = Pedido::withTrashed()->orderBy('id')->pluck('numero_pedido')->all();
        $this->assertSame(["PED-{$anio}-0001", "PED-{$anio}-0002", "PED-{$anio}-0003"], $numeros);
    }

    public function test_el_mostrador_usa_precios_de_la_bd_y_respeta_el_limite_de_descuento()
    {
        $user = $this->usuarioConRol('vendedor');
        $vendedor = Vendedor::factory()->create([
            'user_id' => $user->id,
            'puede_dar_descuentos' => true,
            'descuento_maximo_bs' => 5,
        ]);
        $producto = Producto::factory()->create(['precio_minorista' => 20]);
        $this->ensureInventory($producto->id, 10);

        $venta = fn (float $descuento) => [
            'es_venta_mostrador' => true,
            'cliente_nombre' => 'Mostrador',
            'cliente_email' => 'pos@example.test',
            'metodos_pago_id' => $this->metodoPago()->id,
            'descuento_bs' => $descuento,
            'motivo_descuento' => 'Cliente frecuente',
            // Precio manipulado en el navegador: el servidor lo ignora
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 1, 'subtotal' => 2]],
            'subtotal' => 2,
            'total' => 2,
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/pedidos', $venta(10))
            ->assertStatus(422)->assertJsonValidationErrors('descuento_bs');

        $this->actingAs($user, 'sanctum')->postJson('/api/pedidos', $venta(5))
            ->assertStatus(201);

        $pedido = Pedido::first();
        $this->assertEqualsWithDelta(40.0, (float) $pedido->subtotal, 0.001);
        $this->assertEqualsWithDelta(35.0, (float) $pedido->total, 0.001);
        $this->assertSame($vendedor->id, $pedido->vendedor_id);
        $this->assertDatabaseHas('detalle_pedidos', ['pedidos_id' => $pedido->id, 'precio_unitario' => 20]);
        $this->assertEqualsWithDelta(8.0, $this->stock($producto->id), 0.001);
        $this->assertSame(1, (int) $vendedor->fresh()->ventas_realizadas);
    }

    public function test_los_extras_van_en_linea_aparte_y_no_descuentan_stock()
    {
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create([
            'precio_minorista' => 40,
            'tiene_extras' => true,
            'extras_disponibles' => [['nombre' => 'Jugo', 'precio' => 7]],
        ]);
        $this->ensureInventory($producto->id, 10);

        // Web: formato nuevo (extra_index) y formato antiguo ("5-extra-0")
        $this->crearPedidoWeb([
            ['id' => $producto->id, 'cantidad' => 2],
            ['id' => $producto->id, 'extra_index' => 0, 'cantidad' => 1],
            ['id' => "{$producto->id}-extra-0", 'cantidad' => 1],
        ])->assertStatus(201);

        $pedido = Pedido::first();
        $this->assertEqualsWithDelta(94.0, (float) $pedido->total, 0.001);
        $extras = $pedido->detalles()->where('es_extra', true)->get();
        $this->assertCount(2, $extras);
        $this->assertSame("{$producto->nombre} - Jugo", $extras->first()->nombre_producto);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'entregado'])
            ->assertStatus(200);
        $this->assertEqualsWithDelta(8.0, $this->stock($producto->id), 0.001);

        // Mostrador con extra
        $this->actingAs($admin, 'sanctum')->postJson('/api/pedidos', [
            'es_venta_mostrador' => true,
            'cliente_nombre' => 'Mostrador',
            'cliente_email' => 'pos@example.test',
            'metodos_pago_id' => $this->metodoPago()->id,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
                ['producto_id' => "{$producto->id}-extra-0", 'cantidad' => 1],
            ],
        ])->assertStatus(201);
        $this->assertEqualsWithDelta(7.0, $this->stock($producto->id), 0.001);
    }

    public function test_al_confirmar_se_envia_el_whatsapp()
    {
        Queue::fake();
        $admin = $this->usuarioConRol('admin');
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $pedido = Pedido::first();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200);

        Queue::assertPushed(SendWhatsAppMessage::class);
    }

    public function test_el_correo_de_en_preparacion_usa_el_estado_real()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->crearPedidoWeb([['id' => $producto->id, 'cantidad' => 1]])->assertStatus(201);
        $pedido = Pedido::first();
        $pedido->update(['estado' => 'en_preparacion']);

        $correo = new \App\Mail\PedidoEstadoCambiado($pedido->fresh(['detalles', 'metodoPago']));

        $this->assertStringContainsString('En preparación', $correo->envelope()->subject);
        $this->assertStringContainsString('Estamos preparando tu pedido', $correo->render());
    }
}
