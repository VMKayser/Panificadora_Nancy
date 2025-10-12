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
        $inventario = InventarioProductoFinal::with('producto')
            ->get();

        $resumen = [
            'productos_en_stock' => $inventario->where('stock_actual', '>', 0)->count(),
            'productos_stock_bajo' => $inventario->filter(function ($item) {
                return $item->stock_actual <= $item->stock_minimo && $item->stock_actual > 0;
            })->count(),
            'productos_agotados' => $inventario->where('stock_actual', 0)->count(),
            'valor_total_inventario' => $inventario->sum(function ($item) {
                return $item->stock_actual * $item->costo_promedio;
            }),
            'alertas' => $inventario->filter(function ($item) {
                return $item->stock_actual <= $item->stock_minimo;
            })->map(function ($item) {
                return [
                    'producto' => $item->producto->nombre,
                    'stock_actual' => $item->stock_actual,
                    'stock_minimo' => $item->stock_minimo,
                    'nivel' => $item->stock_actual == 0 ? 'agotado' : 'bajo'
                ];
            })->values()
        ];

        return response()->json($resumen);
    }

    /**
     * Inventario de productos finales
     */
    public function productosFinales(Request $request)
    {
        $query = InventarioProductoFinal::with('producto');

        // Filtros
        if ($request->has('stock_bajo')) {
            $query->whereRaw('stock_actual <= stock_minimo');
        }

        if ($request->has('agotados')) {
            $query->where('stock_actual', 0);
        }

        $inventario = $query->orderBy('stock_actual', 'asc')->get();

        return response()->json($inventario);
    }

    /**
     * Movimientos de productos finales
     */
    public function movimientosProductos(Request $request)
    {
        $query = MovimientoProductoFinal::with(['producto', 'user:id,name']);

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
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $inventario = InventarioProductoFinal::firstOrCreate(
                ['producto_id' => $productoId],
                [
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => 0
                ]
            );

            $stockAnterior = $inventario->stock_actual;
            $diferencia = $request->nuevo_stock - $stockAnterior;

            // Actualizar stock
            $inventario->update([
                'stock_actual' => $request->nuevo_stock
            ]);

            // Registrar movimiento
            MovimientoProductoFinal::create([
                'producto_id' => $productoId,
                'tipo_movimiento' => 'ajuste',
                'cantidad' => abs($diferencia),
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $request->nuevo_stock,
                'user_id' => Auth::id() ?? null,
                'observaciones' => "Motivo: {$request->motivo}. {$request->observaciones}"
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Inventario ajustado exitosamente',
                'data' => $inventario->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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

        $movimientos = MovimientoProductoFinal::with('producto')
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
            ->get();

        $analisis = $movimientos->groupBy('producto_id')->map(function ($grupo) {
            $producto = $grupo->first()->producto;
            $entradas = $grupo->where('tipo_movimiento', 'entrada_produccion')->sum('cantidad');
            $salidas = $grupo->whereIn('tipo_movimiento', ['salida_venta', 'salida_merma', 'salida_degustacion'])->sum('cantidad');
            
            return [
                'producto' => $producto->nombre,
                'entradas' => $entradas,
                'salidas' => $salidas,
                'mermas' => $grupo->where('tipo_movimiento', 'salida_merma')->sum('cantidad'),
                'ventas' => $grupo->where('tipo_movimiento', 'salida_venta')->sum('cantidad'),
                'rotacion' => $entradas > 0 ? ($salidas / $entradas) * 100 : 0,
                'stock_actual' => InventarioProductoFinal::where('producto_id', $producto->id)->first()->stock_actual ?? 0
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

        $movimientos = $query->orderBy('created_at', 'asc')->get();

        $producto = Producto::findOrFail($productoId);

        return response()->json([
            'producto' => $producto,
            'movimientos' => $movimientos,
            'resumen' => [
                'stock_inicial' => $movimientos->first()->stock_anterior ?? 0,
                'stock_final' => $movimientos->last()->stock_nuevo ?? 0,
                'total_entradas' => $movimientos->whereIn('tipo_movimiento', ['entrada_produccion'])->sum('cantidad'),
                'total_salidas' => $movimientos->whereIn('tipo_movimiento', ['salida_venta', 'salida_merma', 'salida_degustacion'])->sum('cantidad')
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

        $mermas = MovimientoProductoFinal::with('producto')
            ->where('tipo_movimiento', 'salida_merma')
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
            ->get();

        $resumen = [
            'periodo' => [
                'desde' => $fechaDesde,
                'hasta' => $fechaHasta
            ],
            'total_mermas' => $mermas->sum('cantidad'),
            'total_registros' => $mermas->count(),
            'por_producto' => $mermas->groupBy('producto_id')->map(function ($grupo) {
                $producto = $grupo->first()->producto;
                $inventario = InventarioProductoFinal::where('producto_id', $producto->id)->first();
                $costoMerma = $grupo->sum('cantidad') * ($inventario->costo_promedio ?? 0);
                
                return [
                    'producto' => $producto->nombre,
                    'cantidad' => $grupo->sum('cantidad'),
                    'registros' => $grupo->count(),
                    'costo_estimado' => $costoMerma
                ];
            })->values(),
            'por_dia' => $mermas->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })->map(function ($grupo, $fecha) {
                return [
                    'fecha' => $fecha,
                    'cantidad' => $grupo->sum('cantidad'),
                    'registros' => $grupo->count()
                ];
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

        $inventario = InventarioProductoFinal::firstOrCreate(
            ['producto_id' => $productoId],
            [
                'stock_actual' => 0,
                'stock_minimo' => $request->stock_minimo,
                'costo_promedio' => 0
            ]
        );

        $inventario->update([
            'stock_minimo' => $request->stock_minimo
        ]);

        return response()->json([
            'message' => 'Stock mínimo configurado exitosamente',
            'data' => $inventario
        ]);
    }
}
