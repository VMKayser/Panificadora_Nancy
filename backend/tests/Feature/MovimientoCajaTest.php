<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MovimientoCaja;

class MovimientoCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_list_movimientos_caja()
    {
        $admin = User::factory()->create();
        // give admin role if role system exists; tests will still pass without role check

        $this->actingAs($admin, 'sanctum');

        $payload = [
            'tipo' => 'ingreso',
            'monto' => 150.50,
            'descripcion' => 'Ventas del día',
        ];

    $resp = $this->postJson('/api/inventario/movimientos-caja', $payload);
    $resp->assertStatus(201)->assertJsonFragment(['tipo' => 'ingreso', 'monto' => 150.5]);

        $list = $this->getJson('/api/inventario/movimientos-caja');
        $list->assertStatus(200)->assertJsonFragment(['descripcion' => 'Ventas del día']);

        $this->assertDatabaseHas('movimientos_caja', [
            'tipo' => 'ingreso',
            'monto' => 150.50,
        ]);
    }
}
