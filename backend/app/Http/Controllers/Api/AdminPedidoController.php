<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class AdminPedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles', 'metodoPago']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($pedidos);
    }

    public function show($id)
    {
        $pedido = Pedido::with(['detalles.producto', 'metodoPago'])->findOrFail($id);
        return response()->json($pedido);
    }

    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['estado' => $request->estado]);
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function updateFechaEntrega(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update([
            'fecha_entrega' => $request->fecha_entrega,
            'hora_entrega' => $request->hora_entrega
        ]);
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function addNotas(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['notas_admin' => $request->notas_admin]);
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function cancel(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update([
            'estado' => 'cancelado',
            'notas_cancelacion' => $request->motivo_cancelacion
        ]);
        return response()->json(['success' => true, 'pedido' => $pedido]);
    }

    public function stats(Request $request)
    {
        $stats = [
            'total_pedidos' => Pedido::count(),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'pedidos_completados' => Pedido::where('estado', 'entregado')->count(),
        ];
        return response()->json($stats);
    }

    public function hoy()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->whereDate('created_at', today())
            ->get();
        return response()->json($pedidos);
    }

    public function pendientes()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->where('estado', 'pendiente')
            ->get();
        return response()->json($pedidos);
    }

    public function paraHoy()
    {
        $pedidos = Pedido::with(['detalles', 'metodoPago'])
            ->whereDate('fecha_entrega', today())
            ->get();
        return response()->json($pedidos);
    }
}
