<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categorias = [
            [
                'nombre' => 'Panes',
                'url' => 'panes',
                'descripcion' => 'Variedad de panes artesanales',
                'esta_activo' => true,
                'order' => 1,
            ],
            [
                'nombre' => 'Empanadas',
                'url' => 'empanadas',
                'descripcion' => 'Empanadas caseras con diferentes rellenos',
                'esta_activo' => true,
                'order' => 2,
            ],
            [
                'nombre' => 'Temporada',
                'url' => 'temporada',
                'descripcion' => 'Productos especiales de temporada',
                'esta_activo' => true,
                'order' => 3,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
