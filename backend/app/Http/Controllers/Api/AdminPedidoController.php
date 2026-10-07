<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendPedidoConfirmadoMail;
use App\Jobs\SendPedidoEstadoCambiadoMail;
use App\Support\HoraNegocio;
use App\Support\SafeTransaction;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AdminPedidoController extends Controller
{
    private const COLUMNAS_LISTADO = ['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','nit_ci_factura','total','estado','estado_pago','created_at','fecha_entrega','hora_entrega','tipo_entrega','direccion_entrega'];

    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(self::COLUMNAS_LISTADO);

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
                if (empty($detalle['es_extra']) && !empty($detalle['producto']) && !empty($detalle['producto']['inventario'])) {
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

        // Estados a los que se puede pasar desde el actual (para el selector del modal)
        $pedidoArray['estados_permitidos'] = array_values(array_filter(
            array_merge(Pedido::FLUJO_ESTADOS, ['cancelado']),
            fn ($estado) => $pedido->puedeCambiarA($estado)
        ));

        $pedidoArray['ganancia'] = round($totalGanancia, 2);

        return response()->json($pedidoArray);
    }

    public function updateEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => ['required', Rule::in(array_merge(Pedido::FLUJO_ESTADOS, ['cancelado']))],
            'motivo_cancelacion' => 'nullable|string|max:1000',
        ]);

        return $this->cambiarEstado(
            Pedido::findOrFail($id),
            $request->estado,
            $request->motivo_cancelacion
        );
    }

    public function updateFechaEntrega(Request $request, $id)
    {
        $request->validate([
            'entrega_datetime' => 'nullable|string',
            'fecha_entrega' => 'required_without:entrega_datetime|nullable|date_format:Y-m-d',
            'hora_entrega' => 'nullable|date_format:H:i,H:i:s',
        ]);

        $pedido = Pedido::findOrFail($id);

        // fecha_entrega guarda fecha y hora juntas (hora local de Bolivia)
        $texto = $request->filled('entrega_datetime')
            ? $request->input('entrega_datetime')
            : trim($request->input('fecha_entrega') . ' ' . ($request->input('hora_entrega') ?: '00:00'));

        $entrega = null;
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $formato) {
            try {
                $entrega = Carbon::createFromFormat($formato, $texto);
                break;
            } catch (\Exception $e) {
                // probar el siguiente formato
            }
        }
        if (!$entrega) {
            return response()->json(['message' => 'Fecha u hora de entrega inválida'], 422);
        }

        $pedido->update([
            'fecha_entrega' => $entrega->format('Y-m-d H:i:s'),
            'hora_entrega' => $request->filled('hora_entrega') || $request->filled('entrega_datetime')
                ? $entrega->format('H:i:s')
                : null,
        ]);
        $this->olvidarCacheListado();
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function addNotas(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['notas_admin' => $request->notas_admin]);
        $this->olvidarCacheListado();
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function cancel(Request $request, $id)
    {
        return $this->cambiarEstado(
            Pedido::findOrFail($id),
            'cancelado',
            $request->motivo_cancelacion
        );
    }

    /**
     * Marca el pago de un pedido (pagado / pendiente / rechazado), independiente
     * de su estado. Los ingresos solo cuentan pedidos pagados.
     */
    public function updatePago(Request $request, $id)
    {
        $request->validate([
            'estado_pago' => ['required', Rule::in(Pedido::ESTADOS_PAGO)],
            'referencia_pago' => 'nullable|string|max:255',
        ]);

        $pedido = Pedido::findOrFail($id);
        $datos = [
            'estado_pago' => $request->estado_pago,
            'fecha_pago' => $request->estado_pago === 'pagado' ? now() : null,
        ];
        if ($request->filled('referencia_pago')) {
            $datos['referencia_pago'] = $request->referencia_pago;
        }
        $pedido->update($datos);

        $this->olvidarCacheListado();
        Cache::forget('inventario.dashboard');
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

        // ingresos_totales: solo pedidos pagados y no cancelados
        $pagados = (clone $query)->where('estado_pago', 'pagado')->where('estado', '!=', 'cancelado');
        $ingresosTotales = (clone $pagados)->sum('total');
        $pedidosPagados = (clone $pagados)->count();

        $promedioPedido = $pedidosPagados > 0 ? ((float) $ingresosTotales / $pedidosPagados) : 0.0;

        $stats = [
            'total_pedidos' => $totalPedidos,
            'ingresos_totales' => (float) $ingresosTotales,
            'promedio_pedido' => round($promedioPedido, 2),
            'pedidos_pagados' => $pedidosPagados,
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
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','nit_ci_factura','total','estado','estado_pago','created_at'])
            ->whereBetween('created_at', HoraNegocio::rangoUtcDelDia())
            ->orderBy('created_at','desc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }

    public function pendientes()
    {
        $pedidos = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','nit_ci_factura','total','estado','estado_pago','created_at'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at','desc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }

    public function paraHoy()
    {
        // fecha_entrega está en hora local de Bolivia
        $pedidos = Pedido::with(['detalles.producto:id,nombre,precio_minorista', 'metodoPago:id,nombre'])
            ->select(['id','numero_pedido','user_id','cliente_id','cliente_nombre','cliente_apellido','cliente_email','cliente_telefono','nit_ci_factura','total','estado','estado_pago','fecha_entrega','hora_entrega'])
            ->whereDate('fecha_entrega', HoraNegocio::hoy())
            ->orderBy('fecha_entrega','asc')
            ->limit(200)
            ->get();
        return response()->json($pedidos);
    }

    /**
     * Aplica un cambio de estado respetando el flujo: solo hacia adelante
     * (se pueden saltar pasos), cancelar si aún no se entregó, y 'entregado'
     * y 'cancelado' son finales. El stock se descuenta/repone en PedidoObserver.
     */
    private function cambiarEstado(Pedido $pedido, string $estadoNuevo, ?string $motivoCancelacion = null)
    {
        $estadoAnterior = $pedido->estado;

        if (!$pedido->puedeCambiarA($estadoNuevo)) {
            $mensaje = in_array($estadoAnterior, Pedido::ESTADOS_FINALES, true)
                ? "El pedido ya está {$estadoAnterior} y no se puede modificar su estado."
                : "No se puede pasar de '{$estadoAnterior}' a '{$estadoNuevo}': los pedidos solo avanzan.";
            return response()->json(['message' => $mensaje], 422);
        }

        $datos = ['estado' => $estadoNuevo];
        if ($estadoNuevo === 'cancelado') {
            $datos['notas_cancelacion'] = $motivoCancelacion;
        }

        SafeTransaction::run(fn () => $pedido->update($datos));

        // Cargar relaciones necesarias para los correos
        $pedido->load(['detalles.producto', 'metodoPago', 'cliente']);

        // Enviar emails según el estado
        try {
            // Check if app-level emails are enabled (this does NOT affect Laravel's built-in account confirmation emails)
            $emailsHabilitados = ConfiguracionSistema::get('emails_habilitados', false);

            if (!$pedido->cliente_email) {
                // Pedido web sin correo: el cliente se entera por WhatsApp
                Log::info("Pedido #{$pedido->id} sin correo: no se envía aviso de estado '{$estadoNuevo}'");
            } elseif ($emailsHabilitados) {
                // Email especial de confirmación (con PedidoConfirmado)
                if ($estadoNuevo === 'confirmado') {
                    // Dispatch mail sending to the queue to avoid blocking the request and reduce memory spikes
                    dispatch(new SendPedidoConfirmadoMail($pedido));
                    Log::info("Queued email de pedido confirmado para {$pedido->cliente_email} pedido #{$pedido->id}");
                }
                // Emails de cambio de estado para otros estados importantes
                elseif (in_array($estadoNuevo, ['en_preparacion', 'listo', 'entregado', 'cancelado'])) {
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

        $this->olvidarCacheListado();

        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    private function olvidarCacheListado(): void
    {
        Cache::forget('pedidos.index.page.1.per.20');
        Cache::forget('pedidos.index.page.1.per.50');
        Cache::forget('pedidos.index.page.1.per.100');
    }
}
