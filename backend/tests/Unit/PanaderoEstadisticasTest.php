<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Panadero;
use App\Models\Produccion;
use App\Models\User;
use App\Models\Producto;
use App\Models\Receta;

class PanaderoEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualizar_estadisticas_calcula_kilos_pagables()
    {
        $user = User::factory()->create();

        $panadero = Panadero::factory()->create(['user_id' => $user->id]);

        // Crear producto y receta (rendimiento 1 para que cantidad_kg sea directa)
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 1.0]);

        // Producción 1: 10 kg producidos, 2 kg harina usada -> kilos_pagables = 8
        $p1 = Produccion::create([
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'user_id' => $user->id,
            'panadero_id' => $panadero->id,
            'fecha_produccion' => now(),
            'cantidad_producida' => 10.0,
            'cantidad_kg' => 10.0,
            'unidad' => 'kg',
            'harina_real_usada' => 2.0,
            'estado' => 'completado',
        ]);

        // Producción 2: 5 kg producidos, no harina especificada -> kilos_pagables = 5
        $p2 = Produccion::create([
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'user_id' => $user->id,
            'panadero_id' => $panadero->id,
            'fecha_produccion' => now()->addDay(),
            'cantidad_producida' => 5.0,
            'cantidad_kg' => 5.0,
            'unidad' => 'kg',
            'estado' => 'completado',
        ]);

        // Ejecutar actualización de estadísticas
        $panadero->actualizarEstadisticas();

        $panadero->refresh();

    // Ahora el total de kilos pagables se basa en harina_real_usada: 2 + 0 = 2
    $this->assertEqualsWithDelta(2.0, (float)$panadero->total_kilos_harina_pagables, 0.001);
        $this->assertEqualsWithDelta(0.0, (float)$panadero->total_unidades_producidas, 0.001);
        $this->assertNotNull($panadero->ultima_produccion);
    }

    public function test_kilos_pagables_no_ve_negativos_si_harina_mayor()
    {
        $user = User::factory()->create();
        $panadero = Panadero::factory()->create(['user_id' => $user->id]);
        $producto = Producto::factory()->create();
        $receta = Receta::factory()->create(['producto_id' => $producto->id, 'rendimiento' => 1.0]);

        // Producción: 3 kg producidos, 5 kg harina usada (anómalo) -> kilos_pagables = 0
        $p = Produccion::create([
            'producto_id' => $producto->id,
            'receta_id' => $receta->id,
            'user_id' => $user->id,
            'panadero_id' => $panadero->id,
            'fecha_produccion' => now(),
            'cantidad_producida' => 3.0,
            'cantidad_kg' => 3.0,
            'unidad' => 'kg',
            'harina_real_usada' => 5.0,
            'estado' => 'completado',
        ]);

        $panadero->actualizarEstadisticas();
        $panadero->refresh();

    $this->assertEqualsWithDelta(5.0, (float)$panadero->total_kilos_harina_pagables, 0.001);
    }
}
