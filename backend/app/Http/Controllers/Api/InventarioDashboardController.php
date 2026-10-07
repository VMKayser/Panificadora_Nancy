<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produccion;
use App\Models\Producto;
use App\Support\HoraNegocio;
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
            // "Hoy" es el día en Bolivia. created_at está en UTC, así que se filtra
            // por el rango UTC de ese día; fecha_produccion ya es una fecha local.
            $hoy = HoraNegocio::hoy();
            $rangoHoy = HoraNegocio::rangoUtcDelDia($hoy);

            // Pedidos y ingresos hoy (los ingresos solo cuentan pedidos pagados)
            $pedidosHoy = Pedido::whereBetween('created_at', $rangoHoy)
                ->where('estado', '!=', 'cancelado')
                ->count();
            $ingresosHoy = (float) Pedido::whereBetween('created_at', $rangoHoy)
                ->where('estado_pago', 'pagado')
                ->where('estado', '!=', 'cancelado')
                ->sum('total');

            // Producción hoy en unidades (las docenas ya están convertidas; los kg no son unidades)
            $produccionHoy = (float) Produccion::whereDate('fecha_produccion', $hoy)
                ->where('estado', 'completado')
                ->sum('cantidad_unidades');

            // Productos activos con stock por debajo del mínimo
            $stockBajo = Producto::where('esta_activo', true)
                ->whereHas('inventario', function($q) {
                    $q->whereColumn('stock_actual', '<=', 'stock_minimo');
                })->count();

            // Panaderos con más producción hoy (por panadero asignado, no por quien la registró)
            $panaderos = Produccion::select('panadero_id', DB::raw('SUM(cantidad_unidades) as produccion'))
                ->whereDate('fecha_produccion', $hoy)
                ->where('estado', 'completado')
                ->whereNotNull('panadero_id')
                ->groupBy('panadero_id')
                ->with('panadero.user')
                ->orderByDesc('produccion')
                ->limit(10)
                ->get()
                ->map(function($p){
                    return [
                        'id' => $p->panadero_id,
                        'nombre' => $p->panadero?->nombre_completo ?? ('Panadero '.$p->panadero_id),
                        'produccion' => (float) $p->produccion
                    ];
                })->values();

            // Productos: unidades vendidas, ingresos y ganancia de los últimos 7 días.
            // Solo pedidos pagados y no cancelados; los extras no cuentan como
            // unidades del producto principal.
            [$desde] = HoraNegocio::rangoUtcDelDia(now(HoraNegocio::zona())->subDays(6)->toDateString());
            $ventasPorProducto = DB::table('detalle_pedidos as d')
                ->join('pedidos as p', 'p.id', '=', 'd.pedidos_id')
                ->whereNull('p.deleted_at')
                ->where('p.estado_pago', 'pagado')
                ->where('p.estado', '!=', 'cancelado')
                ->where('p.created_at', '>=', $desde)
                ->where('d.es_extra', false)
                ->select('d.productos_id', DB::raw('SUM(d.cantidad) as ventas'), DB::raw('SUM(d.subtotal) as ingresos'))
                ->groupBy('d.productos_id')
                ->get();

            $productosInfo = Producto::withTrashed()
                ->with('inventario')
                ->whereIn('id', $ventasPorProducto->pluck('productos_id'))
                ->get()
                ->keyBy('id');

            $productos = [];
            foreach ($ventasPorProducto as $row) {
                $producto = $productosInfo->get($row->productos_id);
                $costoPromedio = (float) ($producto?->inventario?->costo_promedio ?? 0);
                $productos[] = [
                    'id' => $producto?->id ?? $row->productos_id,
                    'nombre' => $producto?->nombre ?? ('Producto '.$row->productos_id),
                    'ventas' => (float) $row->ventas,
                    'ingresos' => round((float) $row->ingresos, 2),
                    // Sin costo registrado la ganancia sería el 100% del ingreso: no se calcula
                    'costo_conocido' => $costoPromedio > 0,
                    'profit' => $costoPromedio > 0
                        ? round((float) $row->ingresos - ($costoPromedio * (float) $row->ventas), 2)
                        : null,
                ];
            }

            // Ventas de los últimos 7 días (días de Bolivia)
            $ventasPorDia = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = now(HoraNegocio::zona())->subDays($i)->toDateString();
                $ventas = (float) Pedido::whereBetween('created_at', HoraNegocio::rangoUtcDelDia($d))
                    ->where('estado_pago', 'pagado')
                    ->where('estado', '!=', 'cancelado')
                    ->sum('total');
                $ventasPorDia[] = ['fecha' => $d, 'ventas' => round($ventas, 2)];
            }

            return [
                'fecha' => $hoy,
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
