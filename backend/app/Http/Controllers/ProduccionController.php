<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Models\Produccion;
use App\Models\Receta;
use App\Models\Producto;
use App\Models\MateriaPrima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\SafeTransaction;
use Illuminate\Validation\ValidationException;
use App\Support\MensajeError;
use App\Http\Controllers\Concerns\ListadoSeguro;

class ProduccionController extends Controller
{
    use ListadoSeguro;

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
            ->paginate($this->porPagina($request, 20));

        return response()->json($producciones);
    }

    /**
     * Registrar nueva producción (Interfaz del panadero)
     *
     * Reglas:
     * - Si el producto no tiene receta y vienen ingredientes, se crea la receta con
     *   ellos. Si ya tiene receta, nunca se modifica: los ingredientes enviados se
     *   descuentan además de la receta, solo para esta producción.
     * - Sin harina_real_usada se usa la harina teórica de la receta.
     * - Todo (receta nueva, producción, descuentos) va en una transacción: si falta
     *   stock no queda nada a medias.
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

        $ingredientes = collect($request->get('ingredientes', []))
            ->map(fn ($ing) => [
                'materia_prima_id' => isset($ing['materia_prima_id']) ? (int) $ing['materia_prima_id'] : null,
                'cantidad' => isset($ing['cantidad']) ? (float) $ing['cantidad'] : 0,
            ])
            ->filter(fn ($ing) => !empty($ing['materia_prima_id']) && $ing['cantidad'] > 0)
            ->values()
            ->all();

        // Si quien registra es un panadero y no se eligió otro, la producción es suya
        $panaderoId = $request->panadero_id ?? Auth::user()?->panadero?->id;

        try {
            [$produccion, $recetaMessage] = SafeTransaction::run(function () use ($request, $ingredientes, $panaderoId) {
                $receta = Receta::where('producto_id', $request->producto_id)
                    ->where('activa', true)
                    ->first();

                $recetaMessage = null;
                $recetaCreada = false;
                if (!$receta && !empty($ingredientes)) {
                    $receta = $this->crearRecetaDesdeProduccion($request, $ingredientes);
                    $recetaCreada = true;
                    $recetaMessage = 'Receta creada automáticamente';
                }

                if (!$receta) {
                    throw ValidationException::withMessages([
                        'producto_id' => 'No hay receta activa para este producto y no se proporcionaron ingredientes',
                    ]);
                }

                $cantidad_producida = (float) $request->cantidad_producida;
                $unidad = $request->unidad ?? 'unidades';
                $harinaReal = (float) ($request->harina_real_usada ?? 0);

                $produccionData = [
                    'producto_id' => $request->producto_id,
                    'receta_id' => $receta->id,
                    'user_id' => Auth::id(),
                    'cantidad_producida' => $cantidad_producida,
                    'fecha_produccion' => $request->fecha_produccion,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'observaciones' => $request->observaciones,
                    'unidad' => $unidad,
                    // null = usar la harina teórica de la receta (se calcula al procesar)
                    'harina_real_usada' => $harinaReal > 0 ? $harinaReal : null,
                    'estado' => 'en_proceso',
                ];

                if ($this->produccionesHasColumn('panadero_id')) {
                    $produccionData['panadero_id'] = $panaderoId;
                }
                if ($this->produccionesHasColumn('cantidad_kg')) {
                    $produccionData['cantidad_kg'] = $unidad === 'kg' ? $cantidad_producida : null;
                }
                if ($this->produccionesHasColumn('cantidad_unidades')) {
                    $produccionData['cantidad_unidades'] = $unidad !== 'kg' ? Receta::aUnidadesStock($cantidad_producida, $unidad) : null;
                }

                $produccion = Produccion::create($produccionData);

                // Con receta recién creada los ingredientes SON la receta (override);
                // con receta existente se suman a ella (extra).
                $modo = $recetaCreada ? 'override' : 'extra';
                $produccion->procesar(array_map(fn ($ing) => $ing + ['modo' => $modo], $ingredientes));

                return [$produccion, $recetaMessage];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (StockInsuficienteException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'ingredientes_faltantes' => $e->detalle,
            ], 422);
        } catch (\InvalidArgumentException $e) {
            // Unidades incompatibles entre receta y producción, etc.
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Log full exception and request payload to help debugging 500s from production registration
            try {
                Log::error('Error al registrar producción', [
                    'exception_message' => $e->getMessage(),
                    'exception_trace' => $e->getTraceAsString(),
                    'user_id' => $request->user()?->id,
                ]);
            } catch (\Exception $_logEx) {
                // If logging fails, swallow to avoid masking original error
                Log::error('Error al intentar loggear excepción de producción: ' . $_logEx->getMessage());
            }

            return response()->json([
                'message' => MensajeError::publico($e, 'Error al registrar producción')
            ], 500);
        }

        // Construir mensaje de éxito
        $successMessage = 'Producción registrada exitosamente';
        if ($recetaMessage) {
            $successMessage .= '. ' . $recetaMessage;
        }

        // Update panadero statistics (best effort)
        $this->actualizarEstadisticasPanadero($produccion);

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'data' => $produccion->load(['producto', 'panadero']),
            'receta_info' => $recetaMessage // Info adicional sobre receta
        ], 201);
    }

    /**
     * Crea la receta del producto a partir de los ingredientes usados en esta
     * producción (cantidades en la unidad de stock de cada materia prima).
     */
    private function crearRecetaDesdeProduccion(Request $request, array $ingredientes): Receta
    {
        // Usar la cantidad producida como rendimiento inicial sensible
        $cantidadProducida = (float) $request->cantidad_producida;
        $rendimiento = max($cantidadProducida, 1);

        $receta = Receta::create([
            'producto_id' => $request->producto_id,
            'activa' => true,
            'nombre_receta' => 'Receta generada automáticamente',
            'descripcion' => 'Creada desde producción el ' . now()->format('d/m/Y H:i'),
            'rendimiento' => $rendimiento,
            'unidad_rendimiento' => $request->unidad ?? 'unidades'
        ]);

        // La columna IngredienteReceta.cantidad representa la cantidad total
        // necesaria para el rendimiento de la receta
        $materias = MateriaPrima::whereIn('id', array_column($ingredientes, 'materia_prima_id'))->get()->keyBy('id');
        foreach ($ingredientes as $ing) {
            $mp = $materias->get($ing['materia_prima_id']);
            $cantidad_por_receta = ($ing['cantidad'] / $cantidadProducida) * $rendimiento;

            $receta->ingredientes()->create([
                'materia_prima_id' => $ing['materia_prima_id'],
                'cantidad' => round(max($cantidad_por_receta, 0.001), 3),
                'unidad' => $mp?->unidad_medida ?? 'kg',
                'orden' => 0
            ]);
        }

        Log::info("Receta creada automáticamente para producto {$request->producto_id}");

        return $receta;
    }

    private function actualizarEstadisticasPanadero(Produccion $produccion): void
    {
        try {
            if ($produccion->panadero) {
                $produccion->panadero->actualizarEstadisticas();
            }
        } catch (\Throwable $_e) {
            // don't block success response if updating stats fails
            Log::warning('No se pudo actualizar estadísticas de panadero: ' . $_e->getMessage());
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
            $produccion->revertir($request->motivo, Auth::id());
        } catch (StockInsuficienteException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => MensajeError::publico($e, 'Error al cancelar producción')
            ], 500);
        }

        $this->actualizarEstadisticasPanadero($produccion);

        return response()->json([
            'message' => 'Producción cancelada exitosamente',
            'data' => $produccion->fresh()
        ]);
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

    /**
     * Determina si la tabla producciones cuenta con una columna en particular.
     * Se cachea el listado para evitar golpear el schema metadata en cada request.
     */
    protected function produccionesHasColumn(string $column): bool
    {
        static $columnCache = null;

        if ($columnCache === null) {
            try {
                $columns = Schema::hasTable('producciones')
                    ? Schema::getColumnListing('producciones')
                    : [];
                $columnCache = array_map('strtolower', $columns);
            } catch (\Throwable $e) {
                $columnCache = [];
                try {
                    Log::warning('No se pudieron obtener las columnas de producciones', [
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Throwable $logError) {
                    // Ignorar errores al intentar loguear
                }
            }
        }

        return in_array(strtolower($column), $columnCache, true);
    }
}
