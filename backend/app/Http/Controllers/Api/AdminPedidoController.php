<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Mail\PedidoEstadoCambiado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminPedidoController extends Controller
{
    /**
     * Listar todos los pedidos
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles', 'metodoPago']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('cliente_nombre', 'like', "%{$search}%")
                  ->orWhere('cliente_apellido', 'like', "%{$search}%")
                  ->orWhere('cliente_email', 'like', "%{$search}%")
                  ->orWhere('cliente_telefono', 'like', "%{$search}%");
            });
        }

        // Ordenar por más recientes
        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($pedidos);
    }

    /**
     * Ver detalle de un pedido
     */
    public function show($id)
    {
        $pedido = Pedido::with(['detalles', 'metodoPago'])->findOrFail($id);
        return response()->json($pedido);
    }

    /**
     * Actualizar estado del pedido
     */
    public function updateEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,confirmado,preparando,listo,en_camino,entregado,cancelado',
            'notas_admin' => 'nullable|string',
            'notas_cancelacion' => 'nullable|string',
        ]);

        $pedido = Pedido::with(['detalles.producto', 'metodoPago'])->findOrFail($id);
        
        $estadoAnterior = $pedido->estado;
        
        $pedido->update([
            'estado' => $validated['estado'],
            'notas_admin' => $validated['notas_admin'] ?? $pedido->notas_admin,
            'notas_cancelacion' => $validated['notas_cancelacion'] ?? $pedido->notas_cancelacion,
        ]);

        // Enviar correo solo si cambió el estado
        if ($estadoAnterior !== $validated['estado']) {
            try {
                Mail::to($pedido->cliente_email)->send(new PedidoEstadoCambiado($pedido));
            } catch (\Exception $e) {
                Log::error('Error enviando correo de cambio de estado: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'pedido' => $pedido->fresh(['detalles', 'metodoPago'])
        ]);
    }

    /**
     * Actualizar fecha de entrega
     */
    public function updateFechaEntrega(Request $request, $id)
    {
        $validated = $request->validate([
            'fecha_entrega' => 'required|date',
            'hora_entrega' => 'nullable|string',
        ]);

        $pedido = Pedido::findOrFail($id);
        
        $pedido->update([
            'fecha_entrega' => $validated['fecha_entrega'],
            'hora_entrega' => $validated['hora_entrega'] ?? $pedido->hora_entrega,
        ]);

        return response()->json([
            'message' => 'Fecha de entrega actualizada',
            'pedido' => $pedido
        ]);
    }

    /**
     * Agregar notas del admin
     */
    public function addNotas(Request $request, $id)
    {
        $validated = $request->validate([
            'notas_admin' => 'required|string',
        ]);

        $pedido = Pedido::findOrFail($id);
        $pedido->update(['notas_admin' => $validated['notas_admin']]);

        return response()->json([
            'message' => 'Notas agregadas',
            'pedido' => $pedido
        ]);
    }

    /**
     * Cancelar pedido
     */
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'motivo_cancelacion' => 'required|string',
        ]);

        $pedido = Pedido::with(['detalles.producto', 'metodoPago'])->findOrFail($id);
        
        $pedido->update([
            'estado' => 'cancelado',
            'notas_cancelacion' => $validated['motivo_cancelacion'],
            'notas_admin' => ($pedido->notas_admin ? $pedido->notas_admin . "\n\n" : '') . 
                           "CANCELADO: " . $validated['motivo_cancelacion'],
        ]);

        // Enviar correo de cancelación
        try {
            Mail::to($pedido->cliente_email)->send(new PedidoEstadoCambiado($pedido));
        } catch (\Exception $e) {
            Log::error('Error enviando correo de cancelación: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Pedido cancelado',
            'pedido' => $pedido
        ]);
    }

    /**
     * Estadísticas de pedidos
     */
    public function stats(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde', now()->startOfMonth());
        $fechaHasta = $request->get('fecha_hasta', now()->endOfMonth());

        $stats = [
            'total_pedidos' => Pedido::whereBetween('created_at', [$fechaDesde, $fechaHasta])->count(),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'pedidos_en_proceso' => Pedido::whereIn('estado', ['confirmado', 'en_preparacion', 'listo'])->count(),
            'pedidos_entregados' => Pedido::where('estado', 'entregado')
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->count(),
            'pedidos_cancelados' => Pedido::where('estado', 'cancelado')
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->count(),
            
            'ingresos_totales' => Pedido::where('estado', 'entregado')
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->sum('total'),
            
            'ingresos_pendientes' => Pedido::whereIn('estado', ['pendiente', 'confirmado', 'en_preparacion', 'listo'])
                ->sum('total'),
            
            'por_estado' => Pedido::select('estado', DB::raw('count(*) as total'))
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->groupBy('estado')
                ->get()
                ->map(function($item) {
                    return [
                        'estado' => $item->estado,
                        'total' => $item->total,
                        'label' => $this->getEstadoLabel($item->estado)
                    ];
                }),
            
            'por_tipo_entrega' => Pedido::select('tipo_entrega', DB::raw('count(*) as total'))
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->groupBy('tipo_entrega')
                ->get(),
            
            'promedio_pedido' => Pedido::where('estado', 'entregado')
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                ->avg('total'),
        ];

        return response()->json($stats);
    }

    /**
     * Pedidos de hoy
     */
    public function hoy()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }

    /**
     * Pedidos pendientes de confirmar
     */
    public function pendientes()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    /**
     * Pedidos para hoy (fecha de entrega)
     */
    public function paraHoy()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->whereDate('fecha_entrega', now())
            ->whereIn('estado', ['confirmado', 'en_preparacion', 'listo'])
            ->orderBy('hora_entrega', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    /**
     * Helper para labels de estado
     */
    private function getEstadoLabel($estado)
    {
        $labels = [
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'en_preparacion' => 'En Preparación',
            'listo' => 'Listo',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
        ];

        return $labels[$estado] ?? $estado;
    }
}
