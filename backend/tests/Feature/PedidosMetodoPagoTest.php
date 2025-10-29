<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\MetodoPago;
use Tests\Traits\InventorySetup;

class PedidosMetodoPagoTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_cliente_puede_crear_pedido_con_metodo_qr_y_efectivo()
    {
        $producto = Producto::factory()->create(['precio_minorista' => 10]);
        $this->ensureInventory($producto->id, 10);

    $mpEf = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);
    $mpQr = MetodoPago::firstOrCreate(['codigo' => 'qr_simple'], ['nombre' => 'QR Simple', 'esta_activo' => true, 'orden' => 2]);

        // Payload minimal para crear pedido (cliente público)
        $payload = [
            'cliente_nombre' => 'Test',
            'cliente_apellido' => 'User',
            'cliente_email' => 'test@example.com',
            'cliente_telefono' => '12345678',
            'productos' => [ ['id' => $producto->id, 'cantidad' => 1] ],
            'metodos_pago_id' => $mpEf->id,
            'tipo_entrega' => 'recoger'
        ];

    $resp = $this->postJson('/api/pedidos', $payload);
    $resp->assertStatus(201)->assertJsonFragment(['estado_pago' => 'pendiente']);

        // ahora con QR
        $payload['metodos_pago_id'] = $mpQr->id;
    $resp2 = $this->postJson('/api/pedidos', $payload);
    $resp2->assertStatus(201)->assertJsonFragment(['estado_pago' => 'pendiente']);
    }
}
