<?php

namespace Tests\Feature;

use App\Jobs\SendPedidoConfirmadoMail;
use App\Models\Cliente;
use App\Models\ConfiguracionSistema;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Checkout simplificado (2026-10-02): el pedido web solo pide nombre y celular.
 */
class ReglasCheckoutSimpleTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    private function pedidoWeb(array $datos = [])
    {
        $producto = Producto::factory()->create(['precio_minorista' => 10]);
        $this->ensureInventory($producto->id, 10);
        $metodo = MetodoPago::firstOrCreate(['codigo' => 'qr'], ['nombre' => 'QR', 'esta_activo' => true, 'orden' => 1]);

        return $this->postJson('/api/pedidos', array_merge([
            'cliente_nombre' => 'Rosa Mamani',
            'cliente_telefono' => '71234567',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $metodo->id,
            'productos' => [['id' => $producto->id, 'cantidad' => 2]],
        ], $datos));
    }

    public function test_basta_con_nombre_y_celular()
    {
        $this->pedidoWeb()->assertStatus(201);

        $pedido = Pedido::first();
        $this->assertNull($pedido->cliente_email);
        $this->assertSame('', $pedido->cliente_apellido);
        $this->assertSame('71234567', $pedido->cliente_telefono);
        $this->assertNull($pedido->cliente->email);
        $this->assertSame('71234567', $pedido->cliente->telefono);
    }

    public function test_sin_correo_el_cliente_se_reconoce_por_el_celular()
    {
        $this->pedidoWeb()->assertStatus(201);
        $this->pedidoWeb()->assertStatus(201);

        $clientes = Cliente::where('telefono', '71234567')->get();
        $this->assertCount(1, $clientes);
        $this->assertSame(2, Pedido::where('cliente_id', $clientes->first()->id)->count());
    }

    public function test_sin_correo_no_se_cuelga_de_un_cliente_con_correo_del_mismo_celular()
    {
        $registrado = Cliente::create([
            'nombre' => 'María', 'apellido' => 'García', 'email' => 'maria@example.test',
            'telefono' => '71234567', 'tipo_cliente' => 'regular', 'activo' => true,
        ]);

        $this->pedidoWeb()->assertStatus(201);

        $this->assertNotSame($registrado->id, Pedido::first()->cliente_id);
    }

    public function test_con_correo_se_sigue_usando_el_correo()
    {
        $this->pedidoWeb(['cliente_email' => 'rosa@example.test'])->assertStatus(201);

        $this->assertSame('rosa@example.test', Pedido::first()->cliente_email);
        $this->assertSame(Pedido::first()->cliente_id, Cliente::where('email', 'rosa@example.test')->value('id'));
    }

    public function test_delivery_acepta_una_direccion_escrita_sin_ubicacion()
    {
        $this->pedidoWeb([
            'tipo_entrega' => 'delivery',
            'direccion_entrega' => 'Calle Bolívar 123, frente a la plaza',
        ])->assertStatus(201);
    }

    public function test_delivery_exige_direccion()
    {
        $this->pedidoWeb(['tipo_entrega' => 'delivery'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('direccion_entrega');
    }

    public function test_delivery_rechaza_una_ubicacion_fuera_de_quillacollo()
    {
        // Plaza Murillo, La Paz
        $this->pedidoWeb([
            'tipo_entrega' => 'delivery',
            'direccion_entrega' => 'Calle Comercio',
            'direccion_lat' => -16.4955,
            'direccion_lng' => -68.1336,
        ])->assertStatus(422);
    }

    public function test_un_pedido_sin_correo_no_encola_correos_de_estado()
    {
        Queue::fake();
        ConfiguracionSistema::set('emails_habilitados', 'true', 'boolean');
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);

        $this->pedidoWeb()->assertStatus(201);
        $pedido = Pedido::first();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200);

        Queue::assertNotPushed(SendPedidoConfirmadoMail::class);
    }
}
