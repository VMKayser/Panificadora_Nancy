<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\MovimientoProductoFinal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ConfiguracionSistema;

class InventarioDashboardController extends Controller
{
    public function index(Request $request)
    {
    // Cache whole dashboard payload for a short TTL to avoid heavy DB load on small servers
    $ttl = 60; // Default 60 seconds if ConfiguracionSistema doesn't exist
    try {
        $ttl = (int) ConfiguracionSistema::get('dashboard_ttl_seconds', 60);
    } catch (\Exception $e) {
        // Si la tabla no existe o hay error, usar valor por defecto
        Log::warning('ConfiguracionSistema no disponible, usando TTL por defecto');
    }
    
    $payload = Cache::remember('inventario.dashboard', $ttl, function() {
            $hoy = date('Y-m-d');

            // Pedidos y ingresos hoy
            $pedidosHoy = Pedido::whereDate('created_at', $hoy)->count();
            $ingresosHoy = (float) Pedido::whereDate('created_at', $hoy)->sum('total');

            // Producción hoy (cantidad_producida de producciones completadas)
            $produccionHoy = (float) Produccion::whereDate('fecha_produccion', $hoy)
                ->where('estado', 'completado')
                ->sum('cantidad_producida');

            // Productos con stock por debajo del mínimo
            $stockBajo = Producto::whereHas('inventario', function($q) {
                $q->whereColumn('stock_actual', '<=', 'stock_minimo');
            })->count();

            // Panaderos con más producción (hoy)
            $panaderos = Produccion::select('user_id', DB::raw('SUM(cantidad_producida) as produccion'))
                ->whereDate('fecha_produccion', $hoy)
                ->where('estado', 'completado')
                ->groupBy('user_id')
                ->with('user')
                ->orderByDesc('produccion')
                ->limit(10)
                ->get()
                ->map(function($p){
                    return [
                        'id' => $p->user_id,
                        'nombre' => $p->user?->name ?? ('Usuario '.$p->user_id),
                        'produccion' => (float) $p->produccion
                    ];
                })->values();

            // Productos: ventas y profit en últimos 7 días
            $fechaDesde = date('Y-m-d', strtotime('-6 days'));
            $ventasPorProducto = DB::table('detalle_pedidos')
                ->select('producto_id', DB::raw('SUM(cantidad) as ventas'), DB::raw('SUM(subtotal) as ingresos'))
                ->whereDate('created_at', '>=', $fechaDesde)
                ->groupBy('producto_id')
                ->get();

            $productos = [];
            foreach ($ventasPorProducto as $row) {
                $producto = Producto::with('inventario')->find($row->producto_id);
                $costoPromedio = $producto?->inventario?->costo_promedio ?? 0;
                $profit = (float) $row->ingresos - ($costoPromedio * (float) $row->ventas);
                $productos[] = [
                    'id' => $producto?->id ?? $row->producto_id,
                    'nombre' => $producto?->nombre ?? ('Producto '.$row->producto_id),
                    'ventas' => (float) $row->ventas,
                    'profit' => round($profit, 2)
                ];
            }

            // Ventas por temporada (últimos 7 días)
            $ventasPorDia = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $ventas = (int) Pedido::whereDate('created_at', $d)->sum('total');
                $ventasPorDia[] = ['fecha' => $d, 'ventas' => $ventas];
            }

            return [
                'pedidos_hoy' => (int) $pedidosHoy,
                'ingresos_hoy' => round($ingresosHoy, 2),
                'produccion_hoy' => (float) $produccionHoy,
                'stock_bajo' => (int) $stockBajo,
                'panaderos' => $panaderos,
                'productos' => $productos,
                'ventas_por_temporada' => $ventasPorDia,
            ];
        });

        return response()->json($payload);
    }
}
