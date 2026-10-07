<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWhatsAppMessage;

class PedidoObserver
{
    /**
     * Manejar el evento "created" del modelo Pedido.
     * La venta de mostrador descuenta su stock en el controlador, dentro de la
     * misma transacción que crea las líneas; aquí solo se invalida la caché.
     */
    public function created(Pedido $pedido)
    {
        try {
            Cache::forget('inventario.dashboard');
        } catch (\Exception $e) {
            Log::warning('No se pudo invalidar cache inventario.dashboard: '.$e->getMessage());
        }
    }

    /**
     * Manejar el evento "updated" del modelo Pedido.
     * Regla de negocio: el stock de un pedido se descuenta cuando sale de la
     * panadería ('entregado') y se repone si se cancela tras haberlo descontado.
     */
    public function updated(Pedido $pedido)
    {
        // Leer el cambio antes de llamar a servicios que puedan tocar el modelo.
        if (!$pedido->isDirty('estado')) {
            return;
        }
        $nuevo = $pedido->estado;

        if ($nuevo === 'entregado' && !$pedido->stock_descargado) {
            (new InventarioService())->descontarInventario($pedido, false);
        }

        if ($nuevo === 'cancelado' && $pedido->stock_descargado) {
            try {
                (new InventarioService())->devolverInventario($pedido, 'cancelación');
            } catch (\Throwable $e) {
                Log::error("Error devolviendo inventario del pedido {$pedido->id}: " . $e->getMessage());
            }
        }

        // Enviar notificación por WhatsApp cuando el pedido pase a 'confirmado' o 'listo'
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
