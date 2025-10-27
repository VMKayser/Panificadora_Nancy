<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Models\MovimientoProductoFinal;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWhatsAppMessage;

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
            // o si ya se descargó el stock
            if ($pedido->detalles()->exists() && !$pedido->stock_descargado) {
                $service = new InventarioService();
                // Usar useTransaction=false porque ya estamos en un observer dentro de una transacción
                $service->descontarInventario($pedido, false);
            } else {
                Log::info("Omitido descontar inventario en created() para pedido {$pedido->id} (sin detalles o ya descargado)");
            }
        }
        
        // Invalidate dashboard cache (short-lived cache) to reflect new pedido
        try { 
            Cache::forget('inventario.dashboard'); 
        } catch (\Exception $e) { 
            Log::warning('No se pudo invalidar cache inventario.dashboard: '.$e->getMessage()); 
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
            // usando el flag stock_descargado o verificando movimientos existentes
            if (!$pedido->stock_descargado) {
                $yaDescontado = MovimientoProductoFinal::where('pedido_id', $pedido->id)
                    ->where('tipo_movimiento', 'salida_venta')
                    ->exists();

                if (!$yaDescontado) {
                    $service = new InventarioService();
                    // Usar useTransaction=false porque ya estamos en un observer dentro de una transacción
                    $service->descontarInventario($pedido, false);
                }
            }
        }

        // Enviar notificación por WhatsApp cuando el pedido pase a 'confirmado' o 'listo'
        if ($pedido->isDirty('estado')) {
            $nuevo = $pedido->estado;
            if (in_array($nuevo, ['confirmado', 'listo'])) {
                try {
                    $telefono = $pedido->cliente_telefono ?? $pedido->cliente->telefono ?? null;
                    if ($telefono) {
                        // Construir mensaje sencillo: nombre + número de pedido + estado
                        $clienteNombre = $pedido->cliente_nombre ?? ($pedido->cliente->nombre ?? 'Cliente');
                        $numero = $pedido->numero_pedido ?? $pedido->id;
                        $estadoText = $nuevo === 'confirmado' ? '✅ Confirmado' : '✨ Listo';
                        $msg = "Hola {$clienteNombre}, tu pedido #{$numero} está {$estadoText}.";
                        SendWhatsAppMessage::dispatch($telefono, $msg);
                        Log::info("WhatsApp job dispatch para pedido {$pedido->id} a {$telefono}");
                    } else {
                        Log::warning("PedidoObserver: no se encontró teléfono para pedido {$pedido->id}, no se envía WhatsApp");
                    }
                } catch (\Exception $e) {
                    Log::error('Error dispatching WhatsApp job: ' . $e->getMessage());
                }
            }
            // Invalidate dashboard cache after state change
            try { Cache::forget('inventario.dashboard'); } catch (\Exception $e) { Log::warning('No se pudo invalidar cache inventario.dashboard: '.$e->getMessage()); }
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
