<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Receta;
use App\Models\IngredienteReceta;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Produccion;
use App\Models\User;

class ProduccionAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_produccion_detects_insufficient_stock_when_requirements_sum_exceeds_available()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear materia prima con stock 10
        $mp = MateriaPrima::factory()->create(['nombre' => 'Harina Test', 'stock_actual' => 10.0, 'costo_unitario' => 1.0]);

        // Crear producto y receta
        $producto = Producto::factory()->create(['nombre' => 'Pan Test']);
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 1.0]);

        // Añadir ingrediente a la receta: requiere 8 unidades
        \App\Models\IngredienteReceta::create([
            'receta_id' => $receta->id,
            'materia_prima_id' => $mp->id,
            'cantidad' => 8.0,
            'unidad' => 'kg',
        ]);

        // Crear producción que produce 1 (factor 1)
        $produccion = Produccion::create([
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'user_id' => $user->id,
            'fecha_produccion' => now(),
            'cantidad_producida' => 1.0,
            'unidad' => 'unidades',
            'estado' => 'en_proceso',
        ]);

        // Añadimos un ingrediente extra que usa la misma materia prima por 6 unidades -> total requerido 14 > stock 10
        $ingredientesExtra = [ ['materia_prima_id' => $mp->id, 'cantidad' => 6.0] ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $produccion->procesar($ingredientesExtra);

        // Al fallar, el stock no debe disminuir
        $mp->refresh();
        $this->assertEqualsWithDelta(10.0, (float)$mp->stock_actual, 0.001);
    }
}
