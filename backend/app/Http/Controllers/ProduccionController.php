<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Receta;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Support\SafeTransaction;

class ProduccionController extends Controller
{
    /**
     * Listar producciones
     */
    public function index(Request $request)
    {
        $query = Produccion::with(['producto', 'receta', 'user:id,name']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_produccion', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_produccion', '<=', $request->fecha_hasta);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $producciones = $query->orderBy('fecha_produccion', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($producciones);
    }

    /**
     * Registrar nueva producción (Interfaz del panadero)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'panadero_id' => 'nullable|exists:panaderos,id',
            'fecha_produccion' => 'required|date',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio',
            'harina_real_usada' => 'nullable|numeric|min:0',
            'cantidad_producida' => 'required|numeric|min:0.001',
            'unidad' => 'required|in:unidades,kg,docenas',
            'observaciones' => 'nullable|string',
            // ingredientes extra: array of { materia_prima_id, cantidad }
            'ingredientes' => 'nullable|array',
            'ingredientes.*.materia_prima_id' => 'required_with:ingredientes|exists:materias_primas,id',
            'ingredientes.*.cantidad' => 'required_with:ingredientes|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Obtener la receta activa del producto y realizar verificaciones antes de abrir la transacción
        $receta = Receta::where('producto_id', $request->producto_id)
            ->where('activa', true)
            ->first();

        if (!$receta) {
            return response()->json([
                'message' => 'No hay receta activa para este producto'
            ], 422);
        }

        // Verificar stock disponible antes de iniciar la transacción
        if (!$receta->verificarStock($request->cantidad_producida)) {
            return response()->json([
                'message' => 'Stock insuficiente de ingredientes',
                'ingredientes_faltantes' => $receta->ingredientes->filter(function ($ingrediente) {
                    return !$ingrediente->materiaPrima->tieneStock($ingrediente->cantidad);
                })->map(function ($ingrediente) {
                    return [
                        'nombre' => $ingrediente->materiaPrima->nombre,
                        'necesario' => $ingrediente->cantidad,
                        'disponible' => $ingrediente->materiaPrima->stock_actual,
                        'unidad' => $ingrediente->materiaPrima->unidad_medida
                    ];
                })->values()
            ], 422);
        }

        try {
            $produccion = SafeTransaction::run(function () use ($request, $receta) {
                // Crear registro de producción
                $produccion = Produccion::create([
                    'producto_id' => $request->producto_id,
                    'receta_id' => $receta->id,
                    'user_id' => Auth::id(),
                    'panadero_id' => $request->panadero_id,
                    'fecha_produccion' => $request->fecha_produccion,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'cantidad_producida' => $request->cantidad_producida,
                    'unidad' => $request->unidad,
                    'harina_real_usada' => $request->harina_real_usada ?? 0,
                    'estado' => 'en_proceso',
                    'observaciones' => $request->observaciones
                ]);

                // Procesar la producción (descuenta inventario)
                $extraIngredientes = $request->get('ingredientes', []);
                $produccion->procesar($extraIngredientes);

                return $produccion;
            });

            return response()->json([
                'message' => 'Producción registrada exitosamente',
                'data' => $produccion->load(['producto', 'receta', 'user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar producción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar producción específica
     */
    public function show($id)
    {
        $produccion = Produccion::with([
            'producto',
            'receta.ingredientes.materiaPrima',
            'user:id,name'
        ])->findOrFail($id);

        return response()->json($produccion);
    }

    /**
     * Actualizar producción (solo si está en proceso)
     */
    public function update(Request $request, $id)
    {
        $produccion = Produccion::findOrFail($id);

        if ($produccion->estado !== 'en_proceso') {
            return response()->json([
                'message' => 'Solo se pueden editar producciones en proceso'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'hora_fin' => 'nullable|date_format:H:i',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $produccion->update($request->only(['hora_fin', 'observaciones']));

        return response()->json([
            'message' => 'Producción actualizada exitosamente',
            'data' => $produccion->fresh()
        ]);
    }

    /**
     * Cancelar producción
     */
    public function cancelar(Request $request, $id)
    {
        $produccion = Produccion::findOrFail($id);

        if ($produccion->estado === 'cancelado') {
            return response()->json([
                'message' => 'La producción ya está cancelada'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prod = SafeTransaction::run(function () use ($produccion, $request) {
                // TODO: Aquí se debería revertir los movimientos de inventario
                // Por ahora solo cambiar el estado
                $produccion->update([
                    'estado' => 'cancelado',
                    'observaciones' => ($produccion->observaciones ?? '') . "\n\nCANCELADO: " . $request->motivo
                ]);
                return $produccion->fresh();
            });

            return response()->json([
                'message' => 'Producción cancelada exitosamente',
                'data' => $prod
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar producción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de producción diaria
     */
    public function reporteDiario(Request $request)
    {
        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        $producciones = Produccion::with(['producto', 'user:id,name'])
            ->whereDate('fecha_produccion', $fecha)
            ->where('estado', 'completado')
            ->get();

        $resumen = [
            'fecha' => $fecha,
            'total_producciones' => $producciones->count(),
            'costo_total' => $producciones->sum('costo_produccion'),
            'productos' => $producciones->groupBy('producto_id')->map(function ($grupo) {
                $producto = $grupo->first()->producto;
                return [
                    'producto' => $producto->nombre,
                    'cantidad_total' => $grupo->sum('cantidad_producida'),
                    'costo_total' => $grupo->sum('costo_produccion'),
                    'producciones' => $grupo->count()
                ];
            })->values(),
            'panaderos' => $producciones->groupBy('user_id')->map(function ($grupo) {
                $user = $grupo->first()->user;
                return [
                    'nombre' => $user->name,
                    'producciones' => $grupo->count(),
                    'cantidad_total' => $grupo->sum('cantidad_producida')
                ];
            })->values()
        ];

        return response()->json($resumen);
    }

    /**
     * Reporte de producción por período
     */
    public function reportePeriodo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $producciones = Produccion::with(['producto'])
            ->whereBetween('fecha_produccion', [$request->fecha_desde, $request->fecha_hasta])
            ->where('estado', 'completado')
            ->get();

        $resumen = [
            'periodo' => [
                'desde' => $request->fecha_desde,
                'hasta' => $request->fecha_hasta
            ],
            'total_producciones' => $producciones->count(),
            'costo_total' => $producciones->sum('costo_produccion'),
            'productos' => $producciones->groupBy('producto_id')->map(function ($grupo) {
                $producto = $grupo->first()->producto;
                return [
                    'producto_id' => $producto->id,
                    'producto' => $producto->nombre,
                    'cantidad_total' => $grupo->sum('cantidad_producida'),
                    'costo_total' => $grupo->sum('costo_produccion'),
                    'costo_promedio' => $grupo->avg('costo_unitario'),
                    'producciones' => $grupo->count()
                ];
            })->values(),
            'por_dia' => $producciones->groupBy(function ($produccion) {
                return $produccion->fecha_produccion;
            })->map(function ($grupo, $fecha) {
                return [
                    'fecha' => $fecha,
                    'producciones' => $grupo->count(),
                    'cantidad_total' => $grupo->sum('cantidad_producida'),
                    'costo_total' => $grupo->sum('costo_produccion')
                ];
            })->values()
        ];

        return response()->json($resumen);
    }

    /**
     * Análisis de diferencias de harina (mermas/excesos)
     */
    public function analisisDiferencias(Request $request)
    {
        $query = Produccion::query()
            ->whereNotNull('diferencia_harina')
            ->where('estado', 'completado');

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_produccion', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_produccion', '<=', $request->fecha_hasta);
        }

        $producciones = $query->with(['producto', 'user:id,name'])->get();

        $analisis = [
            'total_registros' => $producciones->count(),
            'mermas' => $producciones->where('tipo_diferencia', 'merma')->count(),
            'excesos' => $producciones->where('tipo_diferencia', 'exceso')->count(),
            'normales' => $producciones->where('tipo_diferencia', 'normal')->count(),
            'total_diferencia_kg' => $producciones->sum('diferencia_harina'),
            'promedio_diferencia' => $producciones->avg('diferencia_harina'),
            'mayor_merma' => $producciones->where('diferencia_harina', '<', 0)->min('diferencia_harina'),
            'mayor_exceso' => $producciones->where('diferencia_harina', '>', 0)->max('diferencia_harina'),
            'por_producto' => $producciones->groupBy('producto_id')->map(function ($grupo) {
                return [
                    'producto' => $grupo->first()->producto->nombre,
                    'total_diferencia' => $grupo->sum('diferencia_harina'),
                    'promedio' => $grupo->avg('diferencia_harina'),
                    'registros' => $grupo->count()
                ];
            })->values()
        ];

        return response()->json($analisis);
    }
}
