<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MateriaPrima;
use App\Models\MovimientoMateriaPrima;
use App\Models\User;

class MateriaPrimaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_agregar_stock_creates_movimiento_and_updates_stock()
    {
        $user = User::factory()->create();

        $mp = MateriaPrima::create([
            'nombre' => 'Test Harina',
            'unidad_medida' => 'kg',
            'stock_actual' => 0,
            'stock_minimo' => 1,
            'costo_unitario' => 0,
        ]);

    // call with proper parameter order: cantidad, costo_unitario, tipo_movimiento, user_id, numero_factura
    $mp->agregarStock(10, 100, 'entrada_compra', $user->id, 'F001');

        $this->assertDatabaseHas('materias_primas', [
            'id' => $mp->id,
            'stock_actual' => 10,
            'costo_unitario' => 100,
        ]);

        $this->assertDatabaseHas((new MovimientoMateriaPrima())->getTable(), [
            'materia_prima_id' => $mp->id,
            'tipo_movimiento' => 'entrada_compra',
            'cantidad' => 10,
            'user_id' => $user->id,
        ]);
    }

    public function test_descontar_stock_creates_salida_movimiento()
    {
        $user = User::factory()->create();

        $mp = MateriaPrima::create([
            'nombre' => 'Test Azucar',
            'unidad_medida' => 'kg',
            'stock_actual' => 20,
            'stock_minimo' => 1,
            'costo_unitario' => 50,
        ]);

    // use a valid tipo_movimiento expected by the schema
    $mp->descontarStock(5, 'salida_produccion', $user->id);

        $this->assertDatabaseHas('materias_primas', [
            'id' => $mp->id,
            'stock_actual' => 15,
        ]);

        $this->assertDatabaseHas((new MovimientoMateriaPrima())->getTable(), [
            'materia_prima_id' => $mp->id,
            'tipo_movimiento' => 'salida_produccion',
            'cantidad' => 5,
            'user_id' => $user->id,
        ]);
    }
}
