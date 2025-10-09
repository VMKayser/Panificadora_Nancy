<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;

class ProductosConUnidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener categorías
        $panes = Categoria::where('url', 'panes')->first();
        $temporada = Categoria::where('url', 'temporada')->first();
        $empanadas = Categoria::where('url', 'empanadas')->first();

        // Productos básicos por unidad
        $productosBasicos = [
            [
                'categorias_id' => $panes->id,
                'nombre' => 'Pan Especial Tortilla',
                'url' => 'pan-especial-tortilla',
                'descripcion' => 'Pan especial tipo tortilla, fresco y delicioso',
                'descripcion_corta' => 'Pan tipo tortilla',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 1.00,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $panes->id,
                'nombre' => 'Pan Chama',
                'url' => 'pan-chama',
                'descripcion' => 'Pan chama tradicional',
                'descripcion_corta' => 'Pan tradicional',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 1.00,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $panes->id,
                'nombre' => 'Bizcocho',
                'url' => 'bizcocho',
                'descripcion' => 'Bizcocho suave y esponjoso',
                'descripcion_corta' => 'Bizcocho tradicional',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 1.00,
                'esta_activo' => true,
            ],
        ];

        // Masitas a 2.5 bs c/u
        $masitas = [
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Alfajores de Maicena',
                'url' => 'alfajores-maicena',
                'descripcion' => 'Deliciosos alfajores de maicena rellenos de dulce de leche',
                'descripcion_corta' => 'Alfajores rellenos',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 2.50,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $empanadas->id,
                'nombre' => 'Empanaditas de Queso',
                'url' => 'empanaditas-queso',
                'descripcion' => 'Pequeñas empanadas rellenas de queso',
                'descripcion_corta' => 'Empanadas de queso',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 2.50,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Pancitos',
                'url' => 'pancitos',
                'descripcion' => 'Pancitos especiales de royal',
                'descripcion_corta' => 'Pancitos de royal',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 2.50,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Mini Donas',
                'url' => 'mini-donas',
                'descripcion' => 'Pequeñas donas glaseadas',
                'descripcion_corta' => 'Donas mini',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 2.50,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Rosquillas',
                'url' => 'rosquillas',
                'descripcion' => 'Rosquillas crujientes',
                'descripcion_corta' => 'Rosquillas tradicionales',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 2.50,
                'esta_activo' => true,
            ],
        ];

        // TantaWawas (producto con variantes - se necesitará crear variantes después)
        $tantawawas = [
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'TantaWawa 100cm',
                'url' => 'tantawawa-100cm',
                'descripcion' => 'TantaWawa grande de aproximadamente 100 cm',
                'descripcion_corta' => 'TantaWawa grande',
                'unidad_medida' => 'cm',
                'cantidad' => 100,
                'presentacion' => '100 cm aprox.',
                'precio_minorista' => 250.00,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 3,
                'unidad_tiempo' => 'dias',
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'TantaWawa 60cm',
                'url' => 'tantawawa-60cm',
                'descripcion' => 'TantaWawa mediana de aproximadamente 60 cm',
                'descripcion_corta' => 'TantaWawa mediana',
                'unidad_medida' => 'cm',
                'cantidad' => 60,
                'presentacion' => '60 cm aprox.',
                'precio_minorista' => 120.00,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 2,
                'unidad_tiempo' => 'dias',
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'TantaWawa 30cm',
                'url' => 'tantawawa-30cm',
                'descripcion' => 'TantaWawa pequeña de aproximadamente 30 cm',
                'descripcion_corta' => 'TantaWawa pequeña',
                'unidad_medida' => 'cm',
                'cantidad' => 30,
                'presentacion' => '30 cm aprox.',
                'precio_minorista' => 60.00,
                'es_de_temporada' => true,
                'esta_activo' => true,
                'requiere_tiempo_anticipacion' => true,
                'tiempo_anticipacion' => 1,
                'unidad_tiempo' => 'dias',
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'TantaWawa 15cm',
                'url' => 'tantawawa-15cm',
                'descripcion' => 'TantaWawa mini de aproximadamente 15 cm',
                'descripcion_corta' => 'TantaWawa mini',
                'unidad_medida' => 'cm',
                'cantidad' => 15,
                'presentacion' => '15 cm aprox.',
                'precio_minorista' => 30.00,
                'es_de_temporada' => true,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'TantaWawa 7cm',
                'url' => 'tantawawa-7cm',
                'descripcion' => 'TantaWawa pequeñita de aproximadamente 7 cm',
                'descripcion_corta' => 'TantaWawa pequeñita',
                'unidad_medida' => 'cm',
                'cantidad' => 7,
                'presentacion' => '7 cm aprox.',
                'precio_minorista' => 15.00,
                'es_de_temporada' => true,
                'esta_activo' => true,
            ],
        ];

        // Rosquetes
        $rosquetes = [
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Rosquete de Canela',
                'url' => 'rosquete-canela',
                'descripcion' => 'Rosquete tradicional con canela',
                'descripcion_corta' => 'Con canela',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 1.50,
                'esta_activo' => true,
            ],
            [
                'categorias_id' => $temporada->id,
                'nombre' => 'Rosquete Glaseado',
                'url' => 'rosquete-glaseado',
                'descripcion' => 'Rosquete con glase blanco',
                'descripcion_corta' => 'Con glase blanco',
                'unidad_medida' => 'unidad',
                'cantidad' => 1,
                'presentacion' => '1 Unidad',
                'precio_minorista' => 1.50,
                'esta_activo' => true,
            ],
        ];

        // Insertar todos los productos
        foreach (array_merge($productosBasicos, $masitas, $tantawawas, $rosquetes) as $producto) {
            Producto::create($producto);
        }
    }
}
