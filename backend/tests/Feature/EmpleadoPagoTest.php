<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Panadero;
use App\Models\Vendedor;
use App\Models\MetodoPago;

class EmpleadoPagoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_pago_panadero_por_produccion_reduces_kilos()
    {
        // Crear panadero con kilos producidos
        $panadero = Panadero::factory()->create([ 'total_kilos_producidos' => 10 ]);

        $admin = \App\Models\User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);

    $mp = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        $payload = [
            'empleado_tipo' => 'panadero',
            'empleado_id' => $panadero->id,
            'monto' => 200.00,
            'kilos_pagados' => 4,
            'tipo_pago' => 'pago_produccion',
            'metodos_pago_id' => $mp->id,
            'notas' => 'Pago test'
        ];

        $resp = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/empleado-pagos', $payload);
        $resp->assertStatus(201)->assertJson(['success' => true]);

        $panadero->refresh();
        $this->assertEquals(6, $panadero->total_kilos_producidos);
    }

    public function test_registrar_pago_vendedor_comision_reduces_comision_acumulada()
    {
        $vendedor = Vendedor::factory()->create([ 'comision_acumulada' => 150.00 ]);

        $admin = \App\Models\User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);

    $mp = MetodoPago::firstOrCreate(['codigo' => 'transfer'], ['nombre' => 'Transferencia', 'esta_activo' => true, 'orden' => 2]);

        $payload = [
            'empleado_tipo' => 'vendedor',
            'empleado_id' => $vendedor->id,
            'monto' => 100.00,
            'tipo_pago' => 'comision',
            'metodos_pago_id' => $mp->id,
            'notas' => 'Pago comision test'
        ];

        $resp = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/empleado-pagos', $payload);
        $resp->assertStatus(201)->assertJson(['success' => true]);

        $vendedor->refresh();
        $this->assertEquals(50.00, floatval($vendedor->comision_acumulada));
    }

    public function test_no_puede_pagar_mas_comision_de_la_acumulada()
    {
        $vendedor = Vendedor::factory()->create([ 'comision_acumulada' => 30.00 ]);

        $admin = \App\Models\User::factory()->create();
    \App\Models\Role::query()->updateOrInsert(['name' => 'admin'], ['description' => 'Administrador']);
    $role = \App\Models\Role::where('name', 'admin')->first();
    $admin->roles()->attach($role->id);

    $mp = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]);

        $payload = [
            'empleado_tipo' => 'vendedor',
            'empleado_id' => $vendedor->id,
            'monto' => 100.00,
            'tipo_pago' => 'comision',
            'metodos_pago_id' => $mp->id,
        ];

        $resp = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/empleado-pagos', $payload);
        $resp->assertStatus(422)->assertJsonFragment(['message' => 'No se puede pagar más que la comisión acumulada']);

        $vendedor->refresh();
        $this->assertEquals(30.00, floatval($vendedor->comision_acumulada));
    }
}
