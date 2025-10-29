<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use App\Models\Pedido;
use App\Models\DetallePedido;

class InventorySimulateConcurrency extends Command
{
    protected $signature = 'inventory:simulate-concurrency';
    protected $description = 'Crea un pedido de prueba y lanza 2 procesos concurrentes que descuentan inventario';

    public function handle()
    {
        // Crear producto y stock inicial
        $producto = Producto::factory()->create(['precio_minorista' => 5]);
    InventarioProductoFinal::query()->updateOrInsert(['producto_id' => $producto->id], ['stock_actual' => 10, 'costo_promedio' => 5]);

    // Use updateOrInsert to avoid internal savepoint/transaction wrapping by firstOrCreate
    \App\Models\MetodoPago::query()->updateOrInsert(
        ['codigo' => 'efectivo'],
        ['nombre' => 'Efectivo', 'esta_activo' => true, 'orden' => 1]
    );

    $mp = \App\Models\MetodoPago::where('codigo', 'efectivo')->first();

        $pedido = Pedido::create([
            'numero_pedido' => 'SIM-CONC-'.time(),
            'cliente_nombre' => 'Sim Concurrency',
            'cliente_apellido' => 'Test',
            'cliente_email' => 'sim@local',
            'cliente_telefono' => '70000009',
            'subtotal' => 35,
            'total' => 35,
            'metodos_pago_id' => $mp->id,
            'estado' => 'entregado',
            'estado_pago' => 'pagado',
        ]);

        DetallePedido::create([
            'pedidos_id' => $pedido->id,
            'productos_id' => $producto->id,
            'nombre_producto' => $producto->nombre,
            'precio_unitario' => 5,
            'cantidad' => 7,
            'subtotal' => 35,
        ]);

        $this->info("Pedido creado: {$pedido->id}, lanzando 2 procesos concurrentes...");

        $cmd = "php artisan inventory:descontar-pedido {$pedido->id} > /tmp/conc_out_";
        $p1 = popen($cmd . '1 2>&1 & echo $!', 'r');
        $pid1 = trim(fgets($p1));
        pclose($p1);

        $p2 = popen($cmd . '2 2>&1 & echo $!', 'r');
        $pid2 = trim(fgets($p2));
        pclose($p2);

        $this->info("Procesos lanzados: {$pid1}, {$pid2}");
        $this->info('Esperando 2s para permitir ejecución...');
        sleep(2);

        $inv = InventarioProductoFinal::where('producto_id', $producto->id)->first();
        $this->info('Stock final: ' . ($inv->stock_actual ?? 'null'));

        $movs = \App\Models\MovimientoProductoFinal::where('producto_id', $producto->id)->get();
        $this->info('Movimientos registrados: ' . $movs->count());

        return 0;
    }
}
