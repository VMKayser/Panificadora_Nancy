<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Role;
use App\Models\Pedido;
use App\Models\ConfiguracionSistema;
use App\Jobs\SendPedidoConfirmadoMail;

class QueuedEmailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function updating_order_state_to_confirmed_enqueues_a_send_pedido_confirmado_job()
    {
        Queue::fake();

        // Create an admin user and attach admin role
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin->roles()->attach($role->id);

        // Act as admin (sanctum)
        Sanctum::actingAs($admin, ['*']);

        // Ensure emails are enabled in configuration
        ConfiguracionSistema::set('emails_habilitados', 'true', 'boolean');

        // Create a pedido in 'pendiente' state
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        // Call the admin endpoint to update estado to 'confirmado'
        $response = $this->putJson("/api/admin/pedidos/{$pedido->id}/estado", ['estado' => 'confirmado']);

        $response->assertStatus(200);

        // Assert the job was dispatched
        Queue::assertPushed(SendPedidoConfirmadoMail::class, function ($job) use ($pedido) {
            return $job->pedido->id === $pedido->id;
        });
    }
}
