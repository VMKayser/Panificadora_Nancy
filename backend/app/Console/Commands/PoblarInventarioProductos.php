<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\SafeTransaction;

class PoblarInventarioProductos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:poblar-productos {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea filas de inventario para todos los productos activos que no la tengan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productosActivos = Producto::where('esta_activo', true)
            ->whereDoesntHave('inventario')
            ->get();

        if ($productosActivos->isEmpty()) {
            $this->info('✅ Todos los productos activos ya tienen su registro de inventario.');
            return 0;
        }

        $this->info("📦 Se encontraron {$productosActivos->count()} productos activos sin inventario.");

        if (!$this->option('force') && !$this->confirm('¿Deseas crear los registros de inventario?', true)) {
            $this->warn('❌ Operación cancelada.');
            return 1;
        }

        $creados = 0;
        $errores = 0;

        try {
            SafeTransaction::run(function () use ($productosActivos, &$creados, &$errores) {
                foreach ($productosActivos as $producto) {
                    try {
                        InventarioProductoFinal::create([
                            'producto_id' => $producto->id,
                            'stock_actual' => 0,
                            'stock_minimo' => 0,
                            'costo_promedio' => 0,
                        ]);
                        $creados++;
                        $this->line("  ✓ Producto #{$producto->id} - {$producto->nombre}");
                    } catch (\Exception $e) {
                        $errores++;
                        $this->error("  ✗ Error en producto #{$producto->id}: " . $e->getMessage());
                    }
                }
            });

            $this->newLine();
            $this->info("✅ Proceso completado:");
            $this->table(
                ['Resultado', 'Cantidad'],
                [
                    ['Creados', $creados],
                    ['Errores', $errores],
                    ['Total procesados', $productosActivos->count()],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la transacción: ' . $e->getMessage());
            return 1;
        }
    }
}
