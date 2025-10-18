<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Models\MovimientoProductoFinal;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Log;

class PedidoObserver
{
    /**
     * Manejar el evento "created" del modelo Pedido.
     * Cuando se crea un pedido de venta mostrador (entregado inmediatamente),
     * descuenta el inventario automáticamente.
     */
    public function created(Pedido $pedido)
    {
        // Descontar inventario si el pedido fue creado ya como entregado o confirmado
        // (venta mostrador suele crearse como 'entregado', pero algunos flujos pueden crear como 'confirmado')
        if (in_array($pedido->estado, ['entregado', 'confirmado'])) {
            // Evitar descontar si no hay detalles aún (muchos flujos crean pedido y luego detalles)
            if ($pedido->detalles()->exists()) {
                $service = new InventarioService();
                $service->descontarInventario($pedido);
            } else {
                Log::info("Omitido descontar inventario en created() para pedido {$pedido->id} (sin detalles aún)");
            }
        }
    }

    /**
     * Manejar el evento "updated" del modelo Pedido.
     * Cuando el estado cambia a 'entregado', descuenta el inventario.
     */
    public function updated(Pedido $pedido)
    {
        // Solo descontar si el estado cambió a un estado que representa venta: 'entregado' o 'confirmado'
        if ($pedido->isDirty('estado') && in_array($pedido->estado, ['entregado', 'confirmado'])) {
            // Verificar si ya se descontó inventario para este pedido
            $yaDescontado = MovimientoProductoFinal::where('pedido_id', $pedido->id)
                ->where('tipo_movimiento', 'venta')
                ->exists();

            if (!$yaDescontado) {
                $service = new InventarioService();
                $service->descontarInventario($pedido);
            }
        }
    }

    /**
     * Descuenta el inventario de productos finales cuando se entrega un pedido.
     *
     * Nota: la lógica ha sido movida a App\Services\InventarioService. Los controladores
     * que crean pedidos y detalles en la misma transacción deberían invocar ese servicio
     * explícitamente después de crear los detalles para asegurar que el descuento se
     * realiza correctamente.
     */
}
