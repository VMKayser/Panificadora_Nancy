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
            [
                'categorias_id' => 3,
                'nombre' => 'Masitas de Todos Santos',
                'url' => 'masitas-todos-santos',
                'descripcion' => 'Deliciosas masitas dulces decoradas para la festividad',
                'descripcion_corta' => 'Masitas tradicionales',
                'precio_minorista' => 12.00,
                'precio_mayorista' => 10.00,
                'cantidad_minima_mayoreo' => 10,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 48,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
            [
                'categorias_id' => 3,
                'nombre' => 'Pan de Pascua',
                'url' => 'pan-pascua',
                'descripcion' => 'Pan especial navideño con frutas confitadas y nueces',
                'descripcion_corta' => 'Pan navideño tradicional',
                'precio_minorista' => 35.00,
                'precio_mayorista' => 30.00,
                'cantidad_minima_mayoreo' => 3,
                'es_de_temporada' => true,
                'esta_activo' => false,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 72,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
            [
                'categorias_id' => 3,
                'nombre' => 'Buñuelos de Carnaval',
                'url' => 'bunuelos-carnaval',
                'descripcion' => 'Buñuelos crujientes tradicionales de la época de carnaval',
                'descripcion_corta' => 'Buñuelos de carnaval',
                'precio_minorista' => 8.00,
                'precio_mayorista' => 6.50,
                'cantidad_minima_mayoreo' => 12,
                'es_de_temporada' => true,
                'esta_activo' => false,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 24,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
            [
                'categorias_id' => 3,
                'nombre' => 'Rosca de Reyes',
                'url' => 'rosca-reyes',
                'descripcion' => 'Tradicional rosca de reyes con sorpresas, decorada con frutas confitadas',
                'descripcion_corta' => 'Rosca tradicional de reyes',
                'precio_minorista' => 45.00,
                'precio_mayorista' => 40.00,
                'cantidad_minima_mayoreo' => 2,
                'es_de_temporada' => true,
                'esta_activo' => false,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 96,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
            [
                'categorias_id' => 3,
                'nombre' => 'Humintas',
                'url' => 'humintas',
                'descripcion' => 'Humintas dulces o saladas envueltas en hojas de choclo',
                'descripcion_corta' => 'Humintas tradicionales',
                'precio_minorista' => 6.00,
                'precio_mayorista' => 5.00,
                'cantidad_minima_mayoreo' => 15,
                'es_de_temporada' => true,
                'esta_activo' => false,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 24,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
            [
                'categorias_id' => 3,
                'nombre' => 'Tawa Tawas Grande',
                'url' => 'tawa-tawas-grande',
                'descripcion' => 'Versión grande del pan de Todos Santos, ideal para compartir',
                'descripcion_corta' => 'Tawa Tawa grande',
                'precio_minorista' => 25.00,
                'precio_mayorista' => 20.00,
                'cantidad_minima_mayoreo' => 3,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 48,
                'unidad_tiempo' => 'horas',
                'limite_produccion' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
