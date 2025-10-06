<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $productos = [
            // PANES
            [
                'categorias_id' => 1,
                'nombre' => 'Pan Integral',
                'url' => 'pan-integral',
                'descripcion' => 'Pan integral 100% natural, elaborado con harina integral',
                'descripcion_corta' => 'Pan saludable y nutritivo',
                'precio_minorista' => 8.00,
                'precio_mayorista' => 6.50,
                'cantidad_minima_mayoreo' => 10,
                'es_de_temporada' => false,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => false,
            ],
            [
                'categorias_id' => 1,
                'nombre' => 'Pan Francés',
                'url' => 'pan-frances',
                'descripcion' => 'Pan francés crujiente, tradicional de panadería',
                'descripcion_corta' => 'Pan clásico crujiente',
                'precio_minorista' => 5.00,
                'precio_mayorista' => 4.00,
                'cantidad_minima_mayoreo' => 20,
                'es_de_temporada' => false,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => false,
            ],
            
            // EMPANADAS
            [
                'categorias_id' => 2,
                'nombre' => 'Empanadas de Queso',
                'url' => 'empanadas-queso',
                'descripcion' => 'Empanadas rellenas de queso fresco',
                'descripcion_corta' => 'Deliciosas empanadas de queso',
                'precio_minorista' => 3.50,
                'precio_mayorista' => 3.00,
                'cantidad_minima_mayoreo' => 12,
                'es_de_temporada' => false,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => false,
            ],
            
            // TEMPORADA
            [
                'categorias_id' => 3,
                'nombre' => 'TantaWawas',
                'url' => 'tantawawas',
                'descripcion' => 'Pan especial de Todos Santos, elaborado con ingredientes tradicionales',
                'descripcion_corta' => 'Pan tradicional de Todos Santos',
                'precio_minorista' => 15.00,
                'precio_mayorista' => 12.00,
                'cantidad_minima_mayoreo' => 5,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 24,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
