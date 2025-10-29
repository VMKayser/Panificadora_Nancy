<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\MetodoPago;
use App\Models\InventarioProductoFinal;
use App\Models\MateriaPrima;
use App\Models\ImagenProducto;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safety: only run in local/dev environment unless explicitly called
        if (!app()->environment('local') && !($this->command && $this->command->option('force'))) {
            $this->command->info('DemoDataSeeder skipped: not running in local environment. Run with --force to override.');
            return;
        }

        DB::transaction(function () {
            // Categorias
            $categorias = [];
            $names = ['Panes', 'Bolleria', 'Tortas', 'Dulces', 'Bebidas'];
            foreach ($names as $i => $name) {
                Categoria::query()->updateOrInsert(
                    ['nombre' => $name],
                    ['url' => strtolower(str_replace(' ', '-', $name)), 'descripcion' => "$name de ejemplo", 'esta_activo' => true, 'orden' => $i+1]
                );
                $categorias[$i] = Categoria::where('nombre', $name)->first();
            }

            // Metodos de pago
            // Solo Efectivo y un metodo QR (requerimiento)
            $metodos = [
                ['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'esta_activo' => true],
                ['nombre' => 'QR (Tigo Money)', 'codigo' => 'qr-tigo', 'esta_activo' => true],
            ];

            // Use an atomic upsert to make this idempotent and avoid race conditions
            // where a select-then-insert could cause duplicate-key errors.
            DB::table('metodos_pago')->upsert(
                $metodos,
                ['codigo'],
                ['nombre', 'esta_activo']
            );

            // Materias primas
            $materias = ['Harina', 'Azucar', 'Levadura', 'Manteca', 'Sal'];
            foreach ($materias as $mat) {
                MateriaPrima::query()->updateOrInsert(['nombre' => $mat], ['unidad_medida' => 'kg', 'stock_actual' => 100.0, 'activo' => true]);
            }

            // Productos (small sample)
            $samples = [
                ['nombre' => 'Pan Integral', 'categoria' => 0, 'precio_minorista' => 1.20, 'precio_mayorista' => 1.00],
                ['nombre' => 'Miga de Manteca', 'categoria' => 1, 'precio_minorista' => 0.80, 'precio_mayorista' => 0.65],
                ['nombre' => 'Torta de Chocolate', 'categoria' => 2, 'precio_minorista' => 12.00, 'precio_mayorista' => 10.00],
                ['nombre' => 'Rosquilla', 'categoria' => 1, 'precio_minorista' => 0.50, 'precio_mayorista' => 0.40],
                ['nombre' => 'Jugo Natural', 'categoria' => 4, 'precio_minorista' => 2.50, 'precio_mayorista' => 2.00],
            ];

            foreach ($samples as $s) {
                $cat = $categorias[$s['categoria']] ?? $categorias[0];
                $slug = strtolower(str_replace([' ', '/'], ['-', '-'], $s['nombre']));
                Producto::query()->updateOrInsert(
                    ['nombre' => $s['nombre']],
                    [
                        'categorias_id' => $cat->id,
                        'url' => $slug,
                        'descripcion' => $s['nombre'] . ' de ejemplo',
                        'descripcion_corta' => $s['nombre'],
                        'unidad_medida' => 'unidad',
                        'presentacion' => 'unidad',
                        'tiene_variantes' => false,
                        'tiene_extras' => false,
                        'precio_minorista' => $s['precio_minorista'],
                        'precio_mayorista' => $s['precio_mayorista'],
                        'esta_activo' => true,
                    ]
                );
                $producto = Producto::where('nombre', $s['nombre'])->first();

                // Inventario para el producto
                InventarioProductoFinal::query()->updateOrInsert(
                    ['producto_id' => $producto->id],
                    ['stock_actual' => 50, 'stock_minimo' => 5]
                );

                // Imagen de ejemplo
                ImagenProducto::query()->updateOrInsert(
                    ['producto_id' => $producto->id],
                    ['url_imagen' => '/storage/sample-products/' . strtolower(str_replace(' ', '-', $producto->nombre)) . '.jpg', 'es_imagen_principal' => true]
                );
            }

            $this->command->info('Demo data creada: categorias, productos, metodos_pago, inventario y materias_primas (pequeño set).');
        });
    }
}
