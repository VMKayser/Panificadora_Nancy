<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MetodoPago;

class MetodoPagoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_metodos_pago_activos_y_ordenados()
    {
    // Ensure table is clean for this unit test so only these entries are present
    // Use delete() instead of truncate() to avoid foreign key restrictions in testing schema.
    MetodoPago::query()->delete();

    MetodoPago::create(['nombre' => 'A', 'codigo' => 'a', 'esta_activo' => true, 'orden' => 2]);
    MetodoPago::create(['nombre' => 'B', 'codigo' => 'b', 'esta_activo' => true, 'orden' => 1]);
    MetodoPago::create(['nombre' => 'C', 'codigo' => 'c', 'esta_activo' => false, 'orden' => 3]);

        $resp = $this->getJson('/api/metodos-pago');
        $resp->assertStatus(200);

        $data = $resp->json();
        // should only include 2 activos and ordered by 'orden' asc (B then A)
        $this->assertCount(2, $data);
        $this->assertEquals('B', $data[0]['nombre']);
        $this->assertEquals('A', $data[1]['nombre']);
    }
}
