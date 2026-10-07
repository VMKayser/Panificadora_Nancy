<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Services\InventarioService;

class InventoryDescontarPedido extends Command
{
    protected $signature = 'inventory:descontar-pedido {pedidoId}';
    protected $description = 'Descontar inventario para un pedido (uso en simulaciones)';

    public function handle()
    {
        $pedidoId = $this->argument('pedidoId');
        $pedido = Pedido::find($pedidoId);
        if (!$pedido) {
            $this->error("Pedido {$pedidoId} no encontrado");
            return 1;
        }

        try {
            $this->info("Descontando inventario para pedido {$pedido->id}...");
            (new InventarioService())->descontarInventario($pedido, true);
            $this->info('OK');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 2;
        }
    }
}
