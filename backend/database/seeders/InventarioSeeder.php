<?php

namespace Database\Seeders;

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\IngredienteReceta;
use Illuminate\Database\Seeder;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        // ====================================
        // 1. CREAR MATERIAS PRIMAS
        // ====================================
        
        $harina = MateriaPrima::create([
            'nombre' => 'Harina de trigo',
            'codigo_interno' => 'MP001',
            'unidad_medida' => 'kg',
            'stock_actual' => 100.000, // 100 kg iniciales
            'stock_minimo' => 20.000,
            'costo_unitario' => 8.50, // Bs 8.50 por kg
            'proveedor' => 'Distribuidora La Victoria',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $azucar = MateriaPrima::create([
            'nombre' => 'Azúcar refinada',
            'codigo_interno' => 'MP002',
            'unidad_medida' => 'kg',
            'stock_actual' => 50.000,
            'stock_minimo' => 10.000,
            'costo_unitario' => 6.00,
            'proveedor' => 'Distribuidora La Victoria',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $huevos = MateriaPrima::create([
            'nombre' => 'Huevos',
            'codigo_interno' => 'MP003',
            'unidad_medida' => 'unidades',
            'stock_actual' => 200,
            'stock_minimo' => 50,
            'costo_unitario' => 0.80, // Bs 0.80 por huevo
            'proveedor' => 'Granja Avícola',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $manteca = MateriaPrima::create([
            'nombre' => 'Manteca vegetal',
            'codigo_interno' => 'MP004',
            'unidad_medida' => 'kg',
            'stock_actual' => 25.000,
            'stock_minimo' => 5.000,
            'costo_unitario' => 15.00,
            'proveedor' => 'Distribuidora La Victoria',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $levadura = MateriaPrima::create([
            'nombre' => 'Levadura instantánea',
            'codigo_interno' => 'MP005',
            'unidad_medida' => 'kg',
            'stock_actual' => 5.000,
            'stock_minimo' => 1.000,
            'costo_unitario' => 40.00, // Bs 40 por kg
            'proveedor' => 'Distribuidora La Victoria',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $sal = MateriaPrima::create([
            'nombre' => 'Sal fina',
            'codigo_interno' => 'MP006',
            'unidad_medida' => 'kg',
            'stock_actual' => 10.000,
            'stock_minimo' => 2.000,
            'costo_unitario' => 2.50,
            'proveedor' => 'Distribuidora La Victoria',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        $leche = MateriaPrima::create([
            'nombre' => 'Leche líquida',
            'codigo_interno' => 'MP007',
            'unidad_medida' => 'L',
            'stock_actual' => 30.000,
            'stock_minimo' => 10.000,
            'costo_unitario' => 7.00,
            'proveedor' => 'PIL Andina',
            'ultima_compra' => now(),
            'activo' => true,
        ]);

        // ====================================
        // 2. CREAR RECETAS PARA PRODUCTOS
        // ====================================

        // Buscar producto "Bizcocho" (asumiendo que ya existe)
        $bizcocho = Producto::where('nombre', 'LIKE', '%Bizcocho%')->first();
        
        if ($bizcocho) {
            // RECETA: Bizcocho (rinde 50 unidades)
            $recetaBizcocho = Receta::create([
                'producto_id' => $bizcocho->id,
                'nombre_receta' => 'Bizcocho Clásico v1.0',
                'descripcion' => 'Receta estándar de bizcochos. Rinde 50 unidades.',
                'rendimiento' => 50,
                'unidad_rendimiento' => 'unidades',
                'activa' => true,
                'version' => 1,
            ]);

            // Ingredientes del bizcocho (para 50 unidades)
            IngredienteReceta::create([
                'receta_id' => $recetaBizcocho->id,
                'materia_prima_id' => $harina->id,
                'cantidad' => 2.000, // 2 kg de harina
                'unidad' => 'kg',
                'orden' => 1,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaBizcocho->id,
                'materia_prima_id' => $azucar->id,
                'cantidad' => 1.000, // 1 kg de azúcar
                'unidad' => 'kg',
                'orden' => 2,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaBizcocho->id,
                'materia_prima_id' => $huevos->id,
                'cantidad' => 25, // 25 huevos
                'unidad' => 'unidades',
                'orden' => 3,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaBizcocho->id,
                'materia_prima_id' => $manteca->id,
                'cantidad' => 0.500, // 500g de manteca
                'unidad' => 'kg',
                'orden' => 4,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaBizcocho->id,
                'materia_prima_id' => $levadura->id,
                'cantidad' => 0.100, // 100g de levadura
                'unidad' => 'kg',
                'orden' => 5,
            ]);

            // Calcular costos de la receta
            $recetaBizcocho->calcularCostos();

            echo "✅ Receta de Bizcocho creada:\n";
            echo "   - Costo total: Bs {$recetaBizcocho->fresh()->costo_total_calculado}\n";
            echo "   - Costo unitario: Bs {$recetaBizcocho->fresh()->costo_unitario_calculado} por bizcocho\n";
        }

        // Pan de batalla
        $pan = Producto::where('nombre', 'LIKE', '%Pan%')->first();
        
        if ($pan) {
            $recetaPan = Receta::create([
                'producto_id' => $pan->id,
                'nombre_receta' => 'Pan de Batalla v1.0',
                'descripcion' => 'Receta estándar de pan. Rinde 100 unidades.',
                'rendimiento' => 100,
                'unidad_rendimiento' => 'unidades',
                'activa' => true,
                'version' => 1,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaPan->id,
                'materia_prima_id' => $harina->id,
                'cantidad' => 5.000, // 5 kg
                'unidad' => 'kg',
                'orden' => 1,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaPan->id,
                'materia_prima_id' => $levadura->id,
                'cantidad' => 0.200, // 200g
                'unidad' => 'kg',
                'orden' => 2,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaPan->id,
                'materia_prima_id' => $sal->id,
                'cantidad' => 0.150, // 150g
                'unidad' => 'kg',
                'orden' => 3,
            ]);

            IngredienteReceta::create([
                'receta_id' => $recetaPan->id,
                'materia_prima_id' => $azucar->id,
                'cantidad' => 0.300, // 300g
                'unidad' => 'kg',
                'orden' => 4,
            ]);

            $recetaPan->calcularCostos();

            echo "✅ Receta de Pan creada:\n";
            echo "   - Costo total: Bs {$recetaPan->fresh()->costo_total_calculado}\n";
            echo "   - Costo unitario: Bs {$recetaPan->fresh()->costo_unitario_calculado} por pan\n";
        }

        echo "\n✅ Inventario inicial creado exitosamente!\n";
        echo "\n📦 STOCK ACTUAL:\n";
        echo "   - Harina: {$harina->stock_actual} kg\n";
        echo "   - Azúcar: {$azucar->stock_actual} kg\n";
        echo "   - Huevos: {$huevos->stock_actual} unidades\n";
        echo "   - Manteca: {$manteca->stock_actual} kg\n";
        echo "   - Levadura: {$levadura->stock_actual} kg\n";
    }
}
