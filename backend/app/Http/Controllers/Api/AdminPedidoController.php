<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Log;
use App\Mail\PedidoConfirmado;
use App\Mail\PedidoEstadoCambiado;
use App\Jobs\SendPedidoConfirmadoMail;
use App\Jobs\SendPedidoEstadoCambiadoMail;
use Carbon\Carbon;

class AdminPedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','total','estado','created_at','fecha_entrega','hora_entrega','tipo_entrega','direccion_entrega']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $shouldCache = $request->get('page',1) == 1 && !$request->has('estado');
        if ($shouldCache) {
            $cacheKey = "pedidos.index.page.1.per.{$perPage}";
            $pedidos = Cache::remember($cacheKey, 20, function() use ($query, $perPage) {
                return $query->orderBy('created_at', 'desc')->paginate($perPage);
            });
            return response()->json($pedidos);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($pedidos);
    }

    public function show($id)
    {
        // Eager-load nested relations needed by frontend to avoid N+1 and ensure fields exist
        $pedido = Pedido::with([
            'detalles.producto.imagenes',
            'detalles.producto.inventario',
            'metodoPago',
            'cliente',
            'vendedor.user'
        ])->findOrFail($id);

        // Prepare a serializable array and augment with computed cost/profit per detalle
        $pedidoArray = $pedido->toArray();

        $totalGanancia = 0.0;
        if (!empty($pedidoArray['detalles'])) {
            foreach ($pedidoArray['detalles'] as $idx => $detalle) {
                // Try to get costo_promedio from related producto->inventario if available
                $costoPromedio = 0.0;
                if (!empty($detalle['producto']) && !empty($detalle['producto']['inventario'])) {
                    $costoPromedio = (float) ($detalle['producto']['inventario']['costo_promedio'] ?? 0);
                }

                $cantidad = (float) ($detalle['cantidad'] ?? 0);
                $subtotal = (float) ($detalle['subtotal'] ?? ($detalle['precio_unitario'] * $cantidad));

                $costoEstimado = $costoPromedio * $cantidad;
                $gananciaDetalle = $subtotal - $costoEstimado;

                // Write back into array for frontend convenience
                $pedidoArray['detalles'][$idx]['costo_estimado'] = round($costoEstimado, 2);
                $pedidoArray['detalles'][$idx]['ganancia'] = round($gananciaDetalle, 2);

                $totalGanancia += $gananciaDetalle;
            }
        }

        // Add top-level fields expected by frontend
        $pedidoArray['metodo_pago'] = $pedidoArray['metodo_pago'] ?? ($pedidoArray['metodoPago'] ?? null);
        // Ensure cliente object is present
        $pedidoArray['cliente'] = $pedidoArray['cliente'] ?? null;

        // Normalize convenience fields that the frontend modal expects
        // If cliente_* fields are missing but cliente relation exists, populate them
        if (empty($pedidoArray['cliente_nombre']) && !empty($pedidoArray['cliente'])) {
            $pedidoArray['cliente_nombre'] = $pedidoArray['cliente']['nombre'] ?? ($pedidoArray['cliente']['nombre'] ?? null);
        }
        if (empty($pedidoArray['cliente_apellido']) && !empty($pedidoArray['cliente'])) {
            $pedidoArray['cliente_apellido'] = $pedidoArray['cliente']['apellido'] ?? null;
        }
        if (empty($pedidoArray['cliente_email'])) {
            $pedidoArray['cliente_email'] = $pedidoArray['cliente']['email'] ?? ($pedidoArray['cliente_email'] ?? null);
        }
        if (empty($pedidoArray['cliente_telefono'])) {
            $pedidoArray['cliente_telefono'] = $pedidoArray['cliente']['telefono'] ?? ($pedidoArray['cliente_telefono'] ?? null);
        }

        // Ensure numero_pedido exists for the header
        $pedidoArray['numero_pedido'] = $pedidoArray['numero_pedido'] ?? $pedidoArray['id'] ?? null;

        $pedidoArray['ganancia'] = round($totalGanancia, 2);

        return response()->json($pedidoArray);
    }

    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $estadoAnterior = $pedido->estado;
        $estadoNuevo = $request->estado;
        
        $pedido->update(['estado' => $estadoNuevo]);
        
        // Cargar relaciones necesarias para los correos
        $pedido->load(['detalles.producto', 'metodoPago', 'cliente']);
        
        // Enviar emails según el estado
        try {
            // Check if app-level emails are enabled (this does NOT affect Laravel's built-in account confirmation emails)
            $emailsHabilitados = ConfiguracionSistema::get('emails_habilitados', false);

            if ($emailsHabilitados) {
                // Email especial de confirmación (con PedidoConfirmado)
                if ($estadoAnterior !== 'confirmado' && $estadoNuevo === 'confirmado') {
                    // Dispatch mail sending to the queue to avoid blocking the request and reduce memory spikes
                    dispatch(new SendPedidoConfirmadoMail($pedido));
                    Log::info("Queued email de pedido confirmado para {$pedido->cliente_email} pedido #{$pedido->id}");
                }
                // Emails de cambio de estado para otros estados importantes
                elseif (in_array($estadoNuevo, ['preparando', 'listo', 'en_camino', 'entregado', 'cancelado'])) {
                    dispatch(new SendPedidoEstadoCambiadoMail($pedido));
                    Log::info("Queued email de estado '{$estadoNuevo}' para {$pedido->cliente_email} pedido #{$pedido->id}");
                }
            } else {
                // Emails disabled via configuracion_sistema; log and skip sending
                Log::info("Emails deshabilitados por configuración. No se enviará el correo de estado '{$estadoNuevo}' para pedido #{$pedido->id}");
            }
        } catch (\Exception $e) {
            // Loguear el error pero no fallar la actualización
            Log::error("Error enviando correo de estado '{$estadoNuevo}': " . $e->getMessage());
        }
        
        Cache::forget('pedidos.index.page.1.per.20');
        Cache::forget('pedidos.index.page.1.per.50');
        Cache::forget('pedidos.index.page.1.per.100');
        
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function updateFechaEntrega(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        // Allow either fecha_entrega + hora_entrega or a single entrega_datetime (Y-m-d H:i[:s])
        $fechaEntrega = $request->input('fecha_entrega');
        $horaEntrega = $request->input('hora_entrega');

        if ($request->filled('entrega_datetime')) {
            try {
                // Try full seconds first, then fallback to minutes precision
                try {
                    $dt = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('entrega_datetime'));
                } catch (\Exception $e) {
                    $dt = Carbon::createFromFormat('Y-m-d H:i', $request->input('entrega_datetime'));
                }
                $fechaEntrega = $dt->format('Y-m-d H:i:s');
                $horaEntrega = $dt->format('H:i:s');
            } catch (\Exception $e) {
                // Ignore parse errors; validation should handle wrong formats upstream
            }
        }

        $pedido->update([
            'fecha_entrega' => $fechaEntrega,
            'hora_entrega' => $horaEntrega
        ]);
        Cache::forget('pedidos.index.page.1.per.20');
        Cache::forget('pedidos.index.page.1.per.50');
        Cache::forget('pedidos.index.page.1.per.100');
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function addNotas(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['notas_admin' => $request->notas_admin]);
        Cache::forget('pedidos.index.page.1.per.20');
        Cache::forget('pedidos.index.page.1.per.50');
        Cache::forget('pedidos.index.page.1.per.100');
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function cancel(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update([
            'estado' => 'cancelado',
            'notas_cancelacion' => $request->motivo_cancelacion
        ]);
        Cache::forget('pedidos.index.page.1.per.20');
        Cache::forget('pedidos.index.page.1.per.50');
        Cache::forget('pedidos.index.page.1.per.100');
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function stats(Request $request)
    {
        // Allow optional date range filters to compute stats for a specific period
        $query = Pedido::query();
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->get('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->get('fecha_hasta'));
        }

        // total_pedidos: all pedidos in the (optional) range
        $totalPedidos = (clone $query)->count();

        // pedidos_pendientes: estado = 'pendiente'
        $pedidosPendientes = (clone $query)->where('estado', 'pendiente')->count();

        // pedidos_completados: consider 'entregado' as completed
        $pedidosCompletados = (clone $query)->where('estado', 'entregado')->count();

        // total_ventas: sum of totals for pedidos that should be counted as sales
        // Business rule: include 'confirmado', 'en_preparacion', 'listo', 'entregado' as sales
        $estadosVenta = ['confirmado', 'en_preparacion', 'listo', 'entregado'];
        $totalVentas = (clone $query)
            ->whereIn('estado', $estadosVenta)
            ->sum('total');

        // Build por_estado counts
        $estados = ['pendiente','confirmado','en_preparacion','listo','entregado','cancelado'];
        $porEstado = [];
        foreach ($estados as $e) {
            $porEstado[$e] = (clone $query)->where('estado', $e)->count();
        }

        // ingresos_totales: sum of total for all pedidos in range
        $ingresosTotales = (clone $query)->sum('total');

        $promedioPedido = $totalPedidos > 0 ? ((float) $ingresosTotales / $totalPedidos) : 0.0;

        $stats = [
            'total_pedidos' => $totalPedidos,
            'ingresos_totales' => (float) $ingresosTotales,
            'promedio_pedido' => round($promedioPedido, 2),
            'por_estado' => $porEstado,
            'pedidos_pendientes' => $pedidosPendientes,
            'pedidos_completados' => $pedidosCompletados,
            'total_ventas' => (float) $totalVentas,
        ];
        return response()->json($stats);
    }

    public function hoy()
    {
        // Return limited set for today to avoid heavy payloads
        $pedidos = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','total','estado','created_at'])
            ->whereDate('created_at', today())
            ->orderBy('created_at','desc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }

    public function pendientes()
    {
        $pedidos = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','total','estado','created_at'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at','desc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }

    public function paraHoy()
    {
        $pedidos = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','total','estado','fecha_entrega','hora_entrega'])
            ->whereDate('fecha_entrega', today())
            ->orderBy('fecha_entrega','asc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }
}
