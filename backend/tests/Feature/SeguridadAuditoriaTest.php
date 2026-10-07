<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Notifications\ResetPasswordNotification;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InventorySetup;

/**
 * Regresiones de los hallazgos de la auditoría OWASP (SIS316, 2026-10-06).
 */
class SeguridadAuditoriaTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    private function ventaMostrador(): array
    {
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
        $this->ensureInventory($producto->id, 10);
        $metodo = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        return [
            'es_venta_mostrador' => true,
            'cliente_nombre' => 'Cliente POS',
            'cliente_email' => 'pos@example.test',
            'metodos_pago_id' => $metodo->id,
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ];
    }

    private function pedidoWeb(): array
    {
        $producto = Producto::factory()->create(['precio_minorista' => 10]);
        $this->ensureInventory($producto->id, 10);
        $metodo = MetodoPago::firstOrCreate(['codigo' => 'qr'], ['nombre' => 'QR', 'esta_activo' => true, 'orden' => 1]);

        return [
            'cliente_nombre' => 'Rosa Mamani',
            'cliente_telefono' => '71234567',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $metodo->id,
            'productos' => [['id' => $producto->id, 'cantidad' => 1]],
        ];
    }

    // H01: el inventario y la caja ya no son de cualquier usuario autenticado
    public function test_cliente_no_accede_al_inventario_ni_a_la_caja()
    {
        $cliente = $this->crearUsuarioConRol('cliente');
        $this->actingAs($cliente, 'sanctum');

        $this->getJson('/api/inventario/materias-primas')->assertStatus(403);
        $this->postJson('/api/inventario/materias-primas', ['nombre' => 'X'])->assertStatus(403);
        $this->postJson('/api/inventario/movimientos-caja', ['tipo' => 'ingreso', 'monto' => 1000])->assertStatus(403);
        $this->postJson('/api/inventario/producciones', [])->assertStatus(403);
    }

    public function test_panadero_solo_registra_produccion()
    {
        $panadero = $this->crearUsuarioConRol('panadero');
        $this->actingAs($panadero, 'sanctum');

        $this->getJson('/api/inventario/materias-primas')->assertStatus(403);
        // Llega al controlador (422 por validación), no se corta por permisos
        $this->postJson('/api/inventario/producciones', [])->assertStatus(422);
    }

    // H02: venta de mostrador solo para personal autorizado
    public function test_venta_mostrador_anonima_es_rechazada_y_no_descuenta_stock()
    {
        $payload = $this->ventaMostrador();

        $this->postJson('/api/pedidos', $payload)->assertStatus(401);
        $this->assertDatabaseMissing('pedidos', ['cliente_nombre' => 'Cliente POS']);
    }

    public function test_venta_mostrador_con_token_de_cliente_es_rechazada()
    {
        $payload = $this->ventaMostrador();
        $cliente = $this->crearUsuarioConRol('cliente');

        $this->actingAs($cliente, 'sanctum')->postJson('/api/pedidos', $payload)->assertStatus(403);
        $this->actingAs($cliente, 'sanctum')->postJson('/api/admin/ventas-mostrador', $payload)->assertStatus(403);
        $this->assertDatabaseMissing('pedidos', ['cliente_nombre' => 'Cliente POS']);
    }

    public function test_vendedor_registra_venta_mostrador_por_la_ruta_protegida()
    {
        $payload = $this->ventaMostrador();
        $vendedor = $this->crearUsuarioConRol('vendedor');

        $this->actingAs($vendedor, 'sanctum')
            ->postJson('/api/admin/ventas-mostrador', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Venta registrada exitosamente']);
    }

    // H04: límite de tasa en la creación pública de pedidos
    public function test_pedidos_publicos_tienen_limite_de_tasa()
    {
        $payload = $this->pedidoWeb();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/pedidos', $payload)->assertStatus(201);
        }
        $this->postJson('/api/pedidos', $payload)->assertStatus(429);
    }

    // H05: la URL del proveedor de WhatsApp no puede apuntar a la red interna
    public function test_url_saliente_de_whatsapp_rechaza_destinos_internos_y_no_permitidos()
    {
        $this->assertFalse(WhatsAppService::urlSalientePermitida('http://graph.facebook.com/x'));
        $this->assertFalse(WhatsAppService::urlSalientePermitida('https://127.0.0.1:9099/'));
        $this->assertFalse(WhatsAppService::urlSalientePermitida('https://169.254.169.254/latest/meta-data'));
        $this->assertFalse(WhatsAppService::urlSalientePermitida('https://evil.example.com/hook'));
        $this->assertFalse(WhatsAppService::urlSalientePermitida('https://user:pass@graph.facebook.com/'));

        config(['services.whatsapp.allowed_hosts' => ['127.0.0.1']]);
        $this->assertFalse(WhatsAppService::urlSalientePermitida('https://127.0.0.1/'), 'Aun en la lista, una IP interna se bloquea');
    }

    // H06: cabeceras de seguridad presentes y sin X-Powered-By
    public function test_respuestas_de_la_api_llevan_cabeceras_de_seguridad()
    {
        $res = $this->getJson('/api/categorias');

        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'DENY');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $res->headers->get('Content-Security-Policy'));
        $res->assertHeaderMissing('X-Powered-By');
    }

    // H07: el healthcheck público no expone mensajes internos
    public function test_health_no_expone_detalles_de_excepciones()
    {
        $res = $this->getJson('/api/health')->assertStatus(200);
        foreach ($res->json('details') as $detalle) {
            $this->assertContains($detalle, ['db', 'cache']);
        }
    }

    // H10: misma política de contraseñas en el cambio de clave
    public function test_cambio_de_clave_exige_la_politica_del_registro()
    {
        $user = $this->crearUsuarioConRol('cliente', ['password' => bcrypt('Actual-123')]);

        $this->actingAs($user, 'sanctum')->putJson('/api/profile', [
            'current_password' => 'Actual-123',
            'new_password' => 'abcdef',
            'new_password_confirmation' => 'abcdef',
        ])->assertStatus(422)->assertJsonValidationErrors('new_password');
    }

    // H14: parámetros de listado validados
    public function test_parametros_de_orden_invalidos_no_rompen_el_listado()
    {
        $admin = $this->crearUsuarioConRol('admin');
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/inventario/materias-primas?sort_by=no_existe&sort_order=hacker')->assertStatus(200);
        $this->getJson('/api/inventario/materias-primas?per_page=1000000')
            ->assertStatus(200)
            ->assertJsonPath('per_page', 1000);
    }

    // H15 y rutas duplicadas: el vendedor no toca nómina, clientes ni configuración
    public function test_vendedor_no_accede_a_nomina_clientes_ni_configuracion()
    {
        $vendedor = $this->crearUsuarioConRol('vendedor');
        $this->actingAs($vendedor, 'sanctum');

        $this->getJson('/api/admin/empleado-pagos')->assertStatus(403);
        $this->getJson('/api/admin/panaderos')->assertStatus(403);
        $this->getJson('/api/admin/clientes')->assertStatus(403);
        $this->putJson('/api/admin/configuraciones/actualizar-multiples', [
            'configuraciones' => [['clave' => 'whatsapp_api_url', 'valor' => 'https://127.0.0.1/']],
        ])->assertStatus(403);

        // Lo que sí usa el punto de venta sigue disponible
        $this->getJson('/api/admin/categorias')->assertStatus(200);
    }

    // H18: recuperación de contraseña autoservicio
    public function test_recuperacion_de_clave_no_revela_si_el_correo_existe()
    {
        Notification::fake();
        $user = $this->crearUsuarioConRol('cliente', ['email' => 'rosa@example.test']);

        $existe = $this->postJson('/api/forgot-password', ['email' => 'rosa@example.test'])->assertStatus(200);
        $noExiste = $this->postJson('/api/forgot-password', ['email' => 'nadie@example.test'])->assertStatus(200);

        $this->assertSame($existe->json('message'), $noExiste->json('message'));
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_restablecer_clave_con_token_valido_cambia_la_clave_y_cierra_sesiones()
    {
        Notification::fake();
        $user = $this->crearUsuarioConRol('cliente', ['email' => 'rosa@example.test']);
        $user->createToken('auth_token');

        $this->postJson('/api/forgot-password', ['email' => 'rosa@example.test']);
        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = (fn () => $this->token)->call($n);
            return true;
        });

        $datos = fn (string $tok, string $clave) => [
            'token' => $tok, 'email' => 'rosa@example.test',
            'password' => $clave, 'password_confirmation' => $clave,
        ];

        $this->postJson('/api/reset-password', $datos('token-falso', 'Nueva-clave1'))->assertStatus(422);
        $this->postJson('/api/reset-password', $datos($token, 'Nueva-clave1'))->assertStatus(200);

        $this->assertTrue(Hash::check('Nueva-clave1', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        // El token es de un solo uso
        $this->postJson('/api/reset-password', $datos($token, 'Otra-clave22'))->assertStatus(422);
    }
}
