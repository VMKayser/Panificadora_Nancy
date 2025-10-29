<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateDashboardSnapshot extends Command
{
    protected $signature = 'dashboard:generate';
    protected $description = 'Generate a cached dashboard JSON snapshot for admin dashboard';

    public function handle()
    {
        $this->info('Generating dashboard snapshot...');

        // Collect lightweight metrics with optimized queries
        $pedidosHoy = DB::table('pedidos')->whereDate('created_at', DB::raw('CURDATE()'))->count();
        $ingresosHoy = DB::table('pedidos')->whereDate('created_at', DB::raw('CURDATE()'))->where('estado','!=','cancelado')->sum('total');

        $produccionHoy = DB::table('producciones')->whereDate('created_at', DB::raw('CURDATE()'))->sum('cantidad');

        // products with low stock: count
        $stockBajo = DB::table('productos')->whereColumn('stock_actual', '<', 'stock_minimo')->count();

        // top panaderos
        $panaderos = DB::table('producciones as p')
            ->join('empleados as e', 'e.id', '=', 'p.empleado_id')
            ->select('e.id', 'e.nombre', DB::raw('SUM(p.cantidad) as produccion'))
            ->whereDate('p.created_at', DB::raw('CURDATE()'))
            ->groupBy('e.id','e.nombre')
            ->orderByDesc('produccion')
            ->limit(5)
            ->get();

        // top productos (ventas) - last 30 days
        $productos = DB::table('detalle_pedidos as dp')
            ->join('productos as pr', 'pr.id', '=', 'dp.producto_id')
            ->join('pedidos as pe', 'pe.id', '=', 'dp.pedido_id')
            ->select('pr.id', 'pr.nombre', DB::raw('SUM(dp.cantidad) as ventas'), DB::raw('SUM((dp.precio_unitario - IFNULL(pr.costo_unitario,0)) * dp.cantidad) as profit'))
            ->where('pe.created_at', '>=', DB::raw('DATE_SUB(CURDATE(), INTERVAL 30 DAY)'))
            ->groupBy('pr.id','pr.nombre')
            ->orderByDesc('ventas')
            ->limit(10)
            ->get();

        // ventas últimos 7 días
        $ventas7 = DB::table('pedidos')
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as ventas'))
            ->where('created_at', '>=', DB::raw('DATE_SUB(CURDATE(), INTERVAL 7 DAY)'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha')
            ->get();

        $snapshot = [
            'pedidos_hoy' => (int)$pedidosHoy,
            'ingresos_hoy' => (float)$ingresosHoy,
            'produccion_hoy' => (int)$produccionHoy,
            'stock_bajo' => (int)$stockBajo,
            'panaderos' => $panaderos,
            'productos' => $productos,
            'ventas_por_temporada' => $ventas7,
            'generated_at' => now()->toDateTimeString(),
        ];

        Storage::disk('local')->put('dashboard.json', json_encode($snapshot));

        $this->info('Snapshot written to storage/app/dashboard.json');
        return 0;
    }
}
