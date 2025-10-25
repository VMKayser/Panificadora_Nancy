<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MateriaPrima;

class MateriaPrimaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_and_stock_operations_for_materia_prima()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'sanctum');

        // Create
        $payload = [
            'nombre' => 'Harina Prueba',
            'unidad_medida' => 'kg',
            'stock_actual' => 100.0,
            'stock_minimo' => 10.0,
            'costo_unitario' => 1.25,
            'proveedor' => 'Proveedor X'
        ];

        $resp = $this->postJson('/api/inventario/materias-primas', $payload);
        $resp->assertStatus(201)->assertJsonFragment(['nombre' => 'Harina Prueba']);
        $mpId = $resp->json('data.id');

        // Show
        $show = $this->getJson("/api/inventario/materias-primas/{$mpId}");
        $show->assertStatus(200)->assertJsonFragment(['nombre' => 'Harina Prueba']);

        // Update
        $update = $this->putJson("/api/inventario/materias-primas/{$mpId}", ['nombre' => 'Harina Modificada']);
        $update->assertStatus(200)->assertJsonFragment(['nombre' => 'Harina Modificada']);

        // Registrar compra (entrada)
        $compra = $this->postJson("/api/inventario/materias-primas/{$mpId}/compra", [
            'cantidad' => 50,
            'costo_unitario' => 1.30,
            'numero_factura' => 'F123'
        ]);
        $compra->assertStatus(200);
        $this->assertEqualsWithDelta(150.0, (float) $compra->json('data.stock_actual'), 0.001);

        // Ajustar stock a un valor menor
        $ajuste = $this->postJson("/api/inventario/materias-primas/{$mpId}/ajuste", [
            'nuevo_stock' => 80.0,
            'motivo' => 'merma',
            'observaciones' => 'Prueba de merma'
        ]);
        $ajuste->assertStatus(200);
        $this->assertEqualsWithDelta(80.0, (float) $ajuste->json('data.stock_actual'), 0.001);

        // Delete (soft)
        $del = $this->deleteJson("/api/inventario/materias-primas/{$mpId}");
        $del->assertStatus(200);

        $this->assertSoftDeleted('materias_primas', ['id' => $mpId]);
    }
}
