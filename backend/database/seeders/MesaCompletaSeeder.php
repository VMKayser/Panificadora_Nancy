<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;

class MesaCompletaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $temporada = Categoria::where('url', 'temporada')->first();

        // Mesa Completa para Difuntos - use updateOrInsert to avoid creating nested savepoints
        Producto::query()->updateOrInsert(
            ['url' => 'mesa-completa-todos-santos'],
            [
            'categorias_id' => $temporada->id,
            'nombre' => 'Mesa Completa Todos Santos',
            'descripcion' => "MESA COMPLETA PARA DIFUNTOS

Toda la mesa realizada con masa especial:

FIGURA PRINCIPAL:
• 1 T'anta Wawa tamaño grande (60 cm aprox.) - Personalizado (Difunto)

ACOMPAÑANTES Y SÍMBOLOS:
• 2 Acompañantes (hombre y mujer)
• 1 Cruz
• 1 Escalera
• 2 Lunas
• 2 Estrellas
• 2 Soles
• 2 Caballos
• 2 Camellos
• 4 Esquineros

MASITAS Y OFRENDAS:
• 200 unidades de fruta seca
• 200 unidades de maicillos
• 200 unidades de rosquitas
• 2 arrobas de urpu especial para la ofrenda

IMPORTANTE:
⏰ Requiere 2 semanas de anticipación para preparación
🎨 La figura principal puede personalizarse
📦 Incluye todos los elementos tradicionales de la mesa de Todos Santos

EXTRAS OPCIONALES:
Puede agregar masitas especiales adicionales al momento de ordenar.",
            'descripcion_corta' => 'Mesa completa para difuntos con T\'anta Wawa personalizada de 60cm y todos los elementos tradicionales. Incluye 600+ piezas entre masitas, figuras y ofrendas.',
            'unidad_medida' => 'paquete',
            'cantidad' => 1,
            'presentacion' => '1 Mesa Completa',
            'precio_minorista' => 2500.00,
            'es_de_temporada' => true,
            'esta_activo' => true,
            'requiere_tiempo_anticipacion' => true,
            'tiempo_anticipacion' => 14, // 2 semanas
            'unidad_tiempo' => 'dias',
            'extras_disponibles' => [
                [
                    'nombre' => 'Empanaditas de Queso Extras',
                    'descripcion' => 'Pequeñas empanadas rellenas de queso (no incluidas en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=1',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
                [
                    'nombre' => 'Pancitos Extras',
                    'descripcion' => 'Pancitos especiales de royal (no incluidos en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=2',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
                [
                    'nombre' => 'Rollitos de Queso Extras',
                    'descripcion' => 'Rollitos rellenos de queso (no incluidos en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=3',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
                [
                    'nombre' => 'Conitos con Dulce de Leche Extras',
                    'descripcion' => 'Conitos rellenos de dulce de leche (no incluidos en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=4',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
                [
                    'nombre' => 'Alfajores de Maicena Extras',
                    'descripcion' => 'Alfajores rellenos (no incluidos en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=5',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
                [
                    'nombre' => 'Mini Donas Extras',
                    'descripcion' => 'Donas glaseadas (no incluidas en la mesa)',
                    'imagen_url' => 'https://picsum.photos/100/100?random=6',
                    'precio_unitario' => 2.50,
                    'unidad' => 'docena',
                    'cantidad_minima' => 12,
                ],
            ],
        ]);

        // Productos individuales que faltan (Maicillos y Fruta Seca)
        Producto::query()->updateOrInsert(
            ['url' => 'maicillos'],
            [
            'categorias_id' => $temporada->id,
            'nombre' => 'Maicillos',
            'descripcion' => 'Deliciosos maicillos tradicionales para Todos Santos',
            'descripcion_corta' => 'Maicillos tradicionales',
            'unidad_medida' => 'unidad',
            'cantidad' => 1,
            'presentacion' => '1 Unidad',
            'precio_minorista' => 2.00,
            'es_de_temporada' => true,
            'esta_activo' => false, // Por el momento no estamos haciendo
        ]);

        Producto::query()->updateOrInsert(
            ['url' => 'fruta-seca'],
            [
            'categorias_id' => $temporada->id,
            'nombre' => 'Fruta Seca',
            'descripcion' => 'Fruta seca tradicional para ofrendas de Todos Santos',
            'descripcion_corta' => 'Fruta seca para ofrendas',
            'unidad_medida' => 'unidad',
            'cantidad' => 1,
            'presentacion' => '1 Unidad',
            'precio_minorista' => 2.00,
            'es_de_temporada' => true,
            'esta_activo' => false, // Por el momento no estamos haciendo
        ]);

        // Figuras por separado
        Producto::query()->updateOrInsert(
            ['url' => 'figuras-individuales'],
            [
            'categorias_id' => $temporada->id,
            'nombre' => 'Figuras de Masa Individuales',
            'descripcion' => 'Figuras decorativas de masa por separado: cruces, escaleras, lunas, estrellas, soles, caballos, camellos, etc.',
            'descripcion_corta' => 'Figuras decorativas individuales',
            'unidad_medida' => 'unidad',
            'cantidad' => 1,
            'presentacion' => '1 Figura',
            'precio_minorista' => 30.00,
            'es_de_temporada' => true,
            'esta_activo' => true,
            'requiere_tiempo_anticipacion' => true,
            'tiempo_anticipacion' => 3,
            'unidad_tiempo' => 'dias',
        ]);
    }
}
