<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pedido;
use App\Models\Producto;
use Tests\Traits\InventorySetup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PedidosEstadoTest extends TestCase
{
    use RefreshDatabase;
    use InventorySetup;

    public function test_admin_cambia_estado_anade_notas_y_cancelacion()
    {
        $admin = User::factory()->create();
    // Ensure admin role exists and attach it so admin middleware passes
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);
    $producto = Producto::factory()->create(['precio_minorista' => 3]);
    $this->ensureInventory($producto->id, 5);
    $mp = \App\Models\MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        // Crear pedido normal
        $resp = $this->actingAs($admin, 'sanctum')->postJson('/api/pedidos', [
            'cliente_nombre' => 'Cliente',
            'cliente_apellido' => 'Prueba',
            'cliente_email' => 'cli@example.test',
            'cliente_telefono' => '70000002',
            'tipo_entrega' => 'recoger',
            'metodos_pago_id' => $mp->id,
            'productos' => [ ['id' => $producto->id, 'cantidad' => 1] ],
        ])->assertStatus(201);

        $pedido = Pedido::first();

        // Cambiar estado a confirmado
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'confirmado']);

        // Añadir notas
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/pedidos/{$pedido->id}/notas", ['notas_admin' => 'Nota de prueba'])
            ->assertStatus(200);

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'notas_admin' => 'Nota de prueba']);

        // Cancelar
        // The admin cancel endpoint expects 'motivo_cancelacion' as the payload key
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/pedidos/{$pedido->id}/cancelar", ['motivo_cancelacion' => 'Cliente solicitó'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'cancelado', 'notas_cancelacion' => 'Cliente solicitó']);
    }
}
