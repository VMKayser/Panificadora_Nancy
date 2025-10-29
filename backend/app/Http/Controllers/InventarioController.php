<?php

namespace App\Http\Controllers;

use App\Models\InventarioProductoFinal;
use App\Models\MovimientoProductoFinal;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventarioController extends Controller
{
    /**
     * Dashboard general de inventario
     */
    public function dashboard()
    {
        // Build a list that includes all active products, merging with
        // existing InventarioProductoFinal rows. This uses a single query to
        // fetch productos and left join inventario to avoid N+1 and to ensure
        // every active product appears in the dashboard.
    // Use cursor to iterate products without loading entire collection into memory.
    // Aggregate inventory per product to avoid producing multiple rows per product
    // in case inventario_productos_finales stores multiple lots/entries per producto_id.
    $inventarioAgg = DB::table('inventario_productos_finales')
        ->select(
            'producto_id',
            DB::raw('SUM(COALESCE(stock_actual,0)) as stock_actual'),
            DB::raw('MIN(COALESCE(stock_minimo,0)) as stock_minimo'),
            DB::raw('AVG(COALESCE(costo_promedio,0)) as costo_promedio')
        )
        ->groupBy('producto_id');

    $cursor = DB::table('productos as p')
        ->leftJoinSub($inventarioAgg, 'i', 'p.id', '=', 'i.producto_id')
        // Excluir productos soft-deleted: deleted_at IS NOT NULL
        ->where('p.esta_activo', true)
        ->whereNull('p.deleted_at')
            ->select(
                'p.id as producto_id',
                'p.nombre as producto_nombre',
                'i.stock_actual',
                'i.stock_minimo',
                'i.costo_promedio'
            )
            ->cursor();

    $productos_en_stock = 0;
    $productos_stock_bajo = 0;
    $productos_agotados = 0;
    $valor_total_inventario = 0;
    $alertas = [];

    foreach ($cursor as $row) {
        $stock_actual = (float) ($row->stock_actual ?? 0);
        $stock_minimo = (float) ($row->stock_minimo ?? 0);
        $costo_promedio = (float) ($row->costo_promedio ?? 0);

        if ($stock_actual > 0) $productos_en_stock++;
        if ($stock_actual == 0) $productos_agotados++;
        if ($stock_actual <= $stock_minimo && $stock_actual > 0) $productos_stock_bajo++;

        $valor_total_inventario += $stock_actual * $costo_promedio;

        if ($stock_actual <= $stock_minimo) {
            // keep alerts but limit growth if too many
            if (count($alertas) < 500) {
                $alertsNivel = $stock_actual == 0 ? 'agotado' : 'bajo';
                $alertas[] = [
                    'producto' => $row->producto_nombre,
                    'stock_actual' => $stock_actual,
                    'stock_minimo' => $stock_minimo,
                    'nivel' => $alertsNivel
                ];
            }
        }
    }

    $resumen = [
        'productos_en_stock' => $productos_en_stock,
        'productos_stock_bajo' => $productos_stock_bajo,
        'productos_agotados' => $productos_agotados,
        'valor_total_inventario' => $valor_total_inventario,
        'alertas' => $alertas
    ];

    return response()->json($resumen);
    }

    /**
     * Inventario de productos finales
     */
    public function productosFinales(Request $request)
    {
        // Instead of returning only existing inventory records, return a
        // list that includes every active product. Merge inventory data if
        // present; otherwise default stock values to zero.
    // Use aggregated inventory to avoid duplicate product rows when there are
    // multiple inventory records per product (e.g. lotes). Keep fecha_elaboracion
    // and fecha_vencimiento as NULL when aggregated (these are per-lote fields).
    $inventarioAgg = DB::table('inventario_productos_finales')
        ->select(
            'producto_id',
            DB::raw('SUM(COALESCE(stock_actual,0)) as stock_actual'),
            DB::raw('MIN(COALESCE(stock_minimo,0)) as stock_minimo'),
            DB::raw('AVG(COALESCE(costo_promedio,0)) as costo_promedio')
        )
        ->groupBy('producto_id');

    $query = DB::table('productos as p')
        ->leftJoinSub($inventarioAgg, 'i', 'p.id', '=', 'i.producto_id')
        // Only include non-deleted products to keep inventory consistent with public/product listing
        ->where('p.esta_activo', true)
        ->whereNull('p.deleted_at')
            ->select(
                'p.id as producto_id',
                'p.nombre as producto_nombre',
                'i.stock_actual',
                'i.stock_minimo',
                'i.costo_promedio'
            );

        // Apply filters in terms of inventory values (treat missing inventory
        // rows as zeros)
        if ($request->has('stock_bajo')) {
            $query->whereRaw('COALESCE(i.stock_actual,0) <= COALESCE(i.stock_minimo,0)');
        }

        if ($request->has('agotados')) {
            $query->whereRaw('COALESCE(i.stock_actual,0) = 0');
        }

        // If client requests pagination, use it to avoid returning giant payloads.
        if ($request->has('per_page')) {
            $perPage = (int) $request->get('per_page', 20);
            $pag = $query->orderByRaw('COALESCE(i.stock_actual,0) asc')->paginate($perPage);
            $pag->getCollection()->transform(function ($row) {
                return [
                    'producto_id' => $row->producto_id,
                    'producto' => $row->producto_nombre,
                    'stock_actual' => (float) ($row->stock_actual ?? 0),
                    'stock_minimo' => (float) ($row->stock_minimo ?? 0),
                    'costo_promedio' => (float) ($row->costo_promedio ?? 0),
                    'fecha_elaboracion' => $row->fecha_elaboracion ?? null,
                    'fecha_vencimiento' => $row->fecha_vencimiento ?? null,
                ];
            });
            return response()->json($pag);
        }

        $rows = $query->orderByRaw('COALESCE(i.stock_actual,0) asc')->get()->map(function ($row) {
            return [
                'producto_id' => $row->producto_id,
                'producto' => $row->producto_nombre,
                'stock_actual' => (float) ($row->stock_actual ?? 0),
                'stock_minimo' => (float) ($row->stock_minimo ?? 0),
                'costo_promedio' => (float) ($row->costo_promedio ?? 0),
                'fecha_elaboracion' => $row->fecha_elaboracion ?? null,
                'fecha_vencimiento' => $row->fecha_vencimiento ?? null,
            ];
        });

        return response()->json($rows);
    }

    /**
     * Movimientos de productos finales
     */
    public function movimientosProductos(Request $request)
    {
        // Only include movements for products that are not soft-deleted.
        // Using whereHas('producto') ensures the Producto global soft-delete scope
        // filters out trashed products.
        $query = MovimientoProductoFinal::with(['producto', 'user:id,name'])->whereHas('producto');

        // Filtros
        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($movimientos);
    }

    /**
     * Registrar ajuste de inventario de producto final
     */
    public function ajustarInventarioProducto(Request $request, $productoId)
    {
        $validator = Validator::make($request->all(), [
            'nuevo_stock' => 'required|numeric|min:0',
            'motivo' => 'required|string',
            'observaciones' => 'nullable|string',
            'produccion_id' => 'nullable|exists:producciones,id',
            'tipo_movimiento' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($productoId, $request) {
                // Ensure an inventory row exists but do NOT overwrite existing stock_actual.
                // Use an existence check + insert to avoid update semantics that would reset stock.
                $exists = InventarioProductoFinal::where('producto_id', $productoId)->exists();
                if (! $exists) {
                    InventarioProductoFinal::insert([
                        'producto_id' => $productoId,
                        'stock_actual' => 0,
                        'stock_minimo' => 0,
                        'costo_promedio' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $inventario = InventarioProductoFinal::where('producto_id', $productoId)->first();

                $stockAnterior = $inventario->stock_actual;
                $diferencia = $request->nuevo_stock - $stockAnterior;

                // Actualizar stock
                $inventario->update([
                    'stock_actual' => $request->nuevo_stock
                ]);

                // Determinar tipo de movimiento
                $tipoMovimiento = $request->input('tipo_movimiento');
                if (!$tipoMovimiento) {
                    if ($request->has('produccion_id')) {
                        $tipoMovimiento = 'entrada_produccion';
                    } else {
                        $tipoMovimiento = 'ajuste';
                    }
                }

                // Registrar movimiento (incluir produccion_id si viene)
                MovimientoProductoFinal::create([
                    'producto_id' => $productoId,
                    'tipo_movimiento' => $tipoMovimiento,
                    'cantidad' => abs($diferencia),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $request->nuevo_stock,
                    'produccion_id' => $request->input('produccion_id') ?? null,
                    'user_id' => Auth::id() ?? null,
                    'observaciones' => "Motivo: {$request->motivo}. {$request->observaciones}"
                ]);

                return $inventario->fresh();
            });

            return response()->json([
                'message' => 'Inventario ajustado exitosamente',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al ajustar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de rotación de productos
     */
    public function reporteRotacion(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', now()->format('Y-m-d'));
        // Aggregate by producto_id in DB to avoid loading all movimientos into memory
    // Ensure rotation excludes movements related to soft-deleted products by joining productos
        $rows = MovimientoProductoFinal::select(
                'producto_id',
                DB::raw("SUM(CASE WHEN tipo_movimiento = 'entrada_produccion' THEN cantidad ELSE 0 END) as entradas"),
                // Include both legacy 'salida_venta' and 'venta' written by InventarioService
                DB::raw("SUM(CASE WHEN tipo_movimiento IN ('salida_venta','venta','salida_merma','salida_degustacion') THEN cantidad ELSE 0 END) as salidas"),
                DB::raw("SUM(CASE WHEN tipo_movimiento = 'salida_merma' THEN cantidad ELSE 0 END) as mermas"),
                DB::raw("SUM(CASE WHEN tipo_movimiento IN ('salida_venta','venta') THEN cantidad ELSE 0 END) as ventas")
            )
            ->whereBetween('movimientos_productos_finales.created_at', [$fechaDesde, $fechaHasta])
            ->join('productos', 'movimientos_productos_finales.producto_id', '=', 'productos.id')
            ->whereNull('productos.deleted_at')
            ->groupBy('producto_id')
            ->get();

        // Batch fetch product names and inventory to avoid N+1
        $productoIds = $rows->pluck('producto_id')->unique()->values()->all();
        $productos = Producto::whereIn('id', $productoIds)->pluck('nombre','id');
        $inventarios = InventarioProductoFinal::whereIn('producto_id', $productoIds)->pluck('stock_actual','producto_id');

        $analisis = $rows->map(function ($r) use ($productos, $inventarios) {
            $nombre = $productos[$r->producto_id] ?? 'Desconocido';
            $stock_actual = $inventarios[$r->producto_id] ?? 0;
            $entradas = (float) $r->entradas;
            $salidas = (float) $r->salidas;
            return [
                'producto' => $nombre,
                'entradas' => $entradas,
                'salidas' => $salidas,
                'mermas' => (float) $r->mermas,
                'ventas' => (float) $r->ventas,
                'rotacion' => $entradas > 0 ? ($salidas / $entradas) * 100 : 0,
                'stock_actual' => $stock_actual
            ];
        })->values();

        return response()->json([
            'periodo' => [
                'desde' => $fechaDesde,
                'hasta' => $fechaHasta
            ],
            'productos' => $analisis
        ]);
    }

    /**
     * Kardex de producto (historial completo)
     */
    public function kardex($productoId, Request $request)
    {
        $query = MovimientoProductoFinal::where('producto_id', $productoId)
            ->with(['user:id,name', 'produccion', 'pedido']);

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // If dataset is large, paginate to avoid memory spikes
        $count = $query->count();
        if ($count > 2000 || $request->has('per_page')) {
            $perPage = (int) $request->get('per_page', 1000);
            $movPage = $query->orderBy('created_at', 'asc')->paginate($perPage);
            $producto = Producto::findOrFail($productoId);
            return response()->json([
                'producto' => $producto,
                'movimientos' => $movPage,
                'resumen' => null
            ]);
        }

        $movimientos = $query->orderBy('created_at', 'asc')->get();
        $producto = Producto::findOrFail($productoId);

        return response()->json([
            'producto' => $producto,
            'movimientos' => $movimientos,
            'resumen' => [
                'stock_inicial' => $movimientos->first()->stock_anterior ?? 0,
                'stock_final' => $movimientos->last()->stock_nuevo ?? 0,
                'total_entradas' => $movimientos->whereIn('tipo_movimiento', ['entrada_produccion'])->sum('cantidad'),
                // count both 'venta' and 'salida_venta' as sales
                'total_salidas' => $movimientos->whereIn('tipo_movimiento', ['venta','salida_venta', 'salida_merma', 'salida_degustacion'])->sum('cantidad')
            ]
        ]);
    }

    /**
     * Reporte de mermas
     */
    public function reporteMermas(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', now()->format('Y-m-d'));
        // Aggregate mermas in DB to reduce memory usage
        // Exclude movements for soft-deleted products when reporting mermas
        $rows = MovimientoProductoFinal::select(
                'producto_id',
                DB::raw('SUM(cantidad) as cantidad'),
                DB::raw('COUNT(*) as registros')
            )
            ->where('tipo_movimiento', 'salida_merma')
            ->whereBetween('movimientos_productos_finales.created_at', [$fechaDesde, $fechaHasta])
            ->join('productos', 'movimientos_productos_finales.producto_id', '=', 'productos.id')
            ->whereNull('productos.deleted_at')
            ->groupBy('producto_id')
            ->get();

        $total_mermas = (float) MovimientoProductoFinal::where('tipo_movimiento', 'salida_merma')
            ->whereBetween('movimientos_productos_finales.created_at', [$fechaDesde, $fechaHasta])->sum('cantidad');

        // por_dia aggregation
        $porDia = MovimientoProductoFinal::select(DB::raw("DATE(movimientos_productos_finales.created_at) as fecha"), DB::raw('SUM(cantidad) as cantidad'), DB::raw('COUNT(*) as registros'))
            ->where('tipo_movimiento', 'salida_merma')
            ->whereBetween('movimientos_productos_finales.created_at', [$fechaDesde, $fechaHasta])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha')
            ->get();

        // Batch fetch products and inventario to avoid N+1
        $productoIds2 = $rows->pluck('producto_id')->unique()->values()->all();
        $productos2 = Producto::whereIn('id', $productoIds2)->pluck('nombre','id');
        $inventarios2 = InventarioProductoFinal::whereIn('producto_id', $productoIds2)->pluck('costo_promedio','producto_id');

        $por_producto = $rows->map(function ($r) use ($productos2, $inventarios2) {
            $nombre = $productos2[$r->producto_id] ?? 'Desconocido';
            $costoPromedio = $inventarios2[$r->producto_id] ?? 0;
            $costoMerma = (float) $r->cantidad * ($costoPromedio ?? 0);
            return [
                'producto' => $nombre,
                'cantidad' => (float) $r->cantidad,
                'registros' => (int) $r->registros,
                'costo_estimado' => $costoMerma
            ];
        })->values();

        $resumen = [
            'periodo' => [ 'desde' => $fechaDesde, 'hasta' => $fechaHasta ],
            'total_mermas' => $total_mermas,
            'total_registros' => $rows->sum('registros'),
            'por_producto' => $por_producto,
            'por_dia' => $porDia->map(function ($g) {
                return ['fecha' => $g->fecha, 'cantidad' => (float) $g->cantidad, 'registros' => (int) $g->registros];
            })->values()
        ];

        return response()->json($resumen);
    }

    /**
     * Configurar stock mínimo
     */
    public function configurarStockMinimo(Request $request, $productoId)
    {
        $validator = Validator::make($request->all(), [
            'stock_minimo' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure an inventory row exists without overwriting stock_actual.
        $exists = InventarioProductoFinal::where('producto_id', $productoId)->exists();
        if (! $exists) {
            InventarioProductoFinal::insert([
                'producto_id' => $productoId,
                'stock_actual' => 0,
                'stock_minimo' => $request->stock_minimo,
                'costo_promedio' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $inventario = InventarioProductoFinal::where('producto_id', $productoId)->first();

        $inventario->update([
            'stock_minimo' => $request->stock_minimo
        ]);

        return response()->json([
            'message' => 'Stock mínimo configurado exitosamente',
            'data' => $inventario
        ]);
    }
}
