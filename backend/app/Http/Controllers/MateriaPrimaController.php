<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Support\SafeTransaction;
use Illuminate\Validation\ValidationException;
use App\Support\MensajeError;
use App\Http\Controllers\Concerns\ListadoSeguro;

class MateriaPrimaController extends Controller
{
    use ListadoSeguro;

    /**
     * Listar todas las materias primas
     */
    public function index(Request $request)
    {
        $query = MateriaPrima::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo_interno', 'like', "%{$search}%")
                  ->orWhere('proveedor', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        [$sortBy, $sortOrder] = $this->ordenSeguro($request, 'materias_primas', 'nombre', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $materiasPrimas = $query->paginate($this->porPagina($request, 15));

        return response()->json($materiasPrimas);
    }

    /**
     * Obtener materias primas con stock bajo
     */
    public function stockBajo()
    {
        $materiasPrimas = MateriaPrima::whereRaw('stock_actual <= stock_minimo')
            ->where('activo', true)
            ->orderBy('stock_actual', 'asc')
            ->get();

        return response()->json([
            'alertas' => $materiasPrimas->map(function ($mp) {
                return [
                    'id' => $mp->id,
                    'nombre' => $mp->nombre,
                    'stock_actual' => $mp->stock_actual,
                    'stock_minimo' => $mp->stock_minimo,
                    'unidad_medida' => $mp->unidad_medida,
                    'diferencia' => $mp->stock_minimo - $mp->stock_actual,
                    'nivel' => $mp->stock_actual == 0 ? 'critico' : 'bajo'
                ];
            })
        ]);
    }

    /**
     * Crear nueva materia prima
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:200',
            'codigo_interno' => 'nullable|string|max:50|unique:materias_primas,codigo_interno',
            'unidad_medida' => 'required|in:kg,g,L,ml,unidades',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'proveedor' => 'nullable|string|max:200',
            'ultima_compra' => 'nullable|date',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $materiaPrima = MateriaPrima::create($request->all());

        return response()->json([
            'message' => 'Materia prima creada exitosamente',
            'data' => $materiaPrima
        ], 201);
    }

    /**
     * Mostrar una materia prima específica
     */
    public function show($id)
    {
        $materiaPrima = MateriaPrima::with(['movimientos' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(20);
        }])->findOrFail($id);

        return response()->json($materiaPrima);
    }

    /**
     * Actualizar materia prima
     */
    public function update(Request $request, $id)
    {
        $materiaPrima = MateriaPrima::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:200',
            'codigo_interno' => 'nullable|string|max:50|unique:materias_primas,codigo_interno,' . $id,
            'unidad_medida' => 'sometimes|required|in:kg,g,L,ml,unidades',
            'stock_minimo' => 'sometimes|required|numeric|min:0',
            'costo_unitario' => 'sometimes|required|numeric|min:0',
            'proveedor' => 'nullable|string|max:200',
            'ultima_compra' => 'nullable|date',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $materiaPrima->update($request->except(['stock_actual'])); // No permitir cambio directo de stock

        return response()->json([
            'message' => 'Materia prima actualizada exitosamente',
            'data' => $materiaPrima
        ]);
    }

    /**
     * Eliminar (soft delete) materia prima
     */
    public function destroy($id)
    {
        $materiaPrima = MateriaPrima::findOrFail($id);
        
        // Verificar si está en uso en recetas activas
        $enUso = DB::table('ingredientes_receta')
            ->join('recetas', 'ingredientes_receta.receta_id', '=', 'recetas.id')
            ->where('ingredientes_receta.materia_prima_id', $id)
            ->where('recetas.activa', true)
            ->whereNull('recetas.deleted_at')
            ->exists();

        if ($enUso) {
            return response()->json([
                'message' => 'No se puede eliminar. La materia prima está en uso en recetas activas.'
            ], 422);
        }

        $materiaPrima->delete();

        return response()->json([
            'message' => 'Materia prima eliminada exitosamente'
        ]);
    }

    /**
     * Registrar compra (entrada de stock)
     */
    public function registrarCompra(Request $request, $id)
    {
        try { 
            \Illuminate\Support\Facades\Log::info('API registrarCompra called', ['id' => $id, 'payload' => $request->all(), 'user_id' => Auth::id()]);
        } catch (\Throwable $e) { /* ignore logging failures */ }

        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric|min:0.001',
            'costo_unitario' => 'required|numeric|min:0',
            'numero_factura' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $materiaPrima = MateriaPrima::findOrFail($id);

        try {
            // Idempotency support: if client provides idempotency_key, check and reuse
            $idemKey = $request->input('idempotency_key') ?? $request->header('Idempotency-Key');
            if ($idemKey) {
                try {
                    $existing = \App\Models\IdempotencyKey::where('key', $idemKey)->first();
                    if ($existing && $existing->response_data) {
                        // Return stored response to caller (avoid processing twice)
                        return json_decode(json_encode($existing->response_data));
                    }
                } catch (\Throwable $e) { /* ignore lookup errors and continue */ }
            }

            $result = SafeTransaction::run(function () use ($materiaPrima, $request, $idemKey) {
                $stockAnterior = $materiaPrima->stock_actual;

                // Agregar stock (el método agrega el movimiento internamente)
                $materiaPrima->agregarStock(
                    $request->cantidad,
                    $request->costo_unitario,
                    'entrada_compra',
                    Auth::id(),
                    $request->numero_factura,
                    $request->observaciones
                );

                // Actualizar fecha de última compra
                $materiaPrima->update([
                    'ultima_compra' => now(),
                    'costo_unitario' => $request->costo_unitario // Actualizar con el nuevo costo
                ]);

                $fresh = $materiaPrima->fresh();

                // Store idempotency record if key present
                if ($idemKey) {
                    try {
                        \App\Models\IdempotencyKey::updateOrCreate(
                            ['key' => $idemKey],
                            [
                                'user_id' => Auth::id(),
                                'endpoint' => '/inventario/materias-primas/{id}/compra',
                                'request_hash' => json_encode($request->all()),
                                'response_data' => ['message' => 'Compra registrada exitosamente', 'data' => $fresh],
                            ]
                        );
                    } catch (\Throwable $_e) { /* ignore store errors */ }
                }

                return $fresh;
            });

            return response()->json([
                'message' => 'Compra registrada exitosamente',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => MensajeError::publico($e, 'Error al registrar compra')
            ], 500);
        }
    }

    /**
     * Ajustar stock manualmente
     */
    public function ajustarStock(Request $request, $id)
    {
        // cantidad + direccion: el servidor aplica el cambio sobre el stock actual.
        // nuevo_stock: fija el stock (conteo físico), se mantiene por compatibilidad.
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required_without:nuevo_stock|nullable|numeric|gt:0',
            'direccion' => 'required_with:cantidad|nullable|in:entrada,salida',
            'nuevo_stock' => 'required_without:cantidad|nullable|numeric|min:0',
            'motivo' => 'required|in:inventario_fisico,merma,correccion,devolucion,degustacion',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $materiaPrima = MateriaPrima::findOrFail($id);

        try {
            $result = SafeTransaction::run(function () use ($materiaPrima, $request) {
                $bloqueada = MateriaPrima::whereKey($materiaPrima->id)->lockForUpdate()->first();
                $stockAnterior = (float) $bloqueada->stock_actual;

                if ($request->filled('cantidad')) {
                    $cambio = (float) $request->cantidad * ($request->direccion === 'salida' ? -1 : 1);
                    $nuevoStock = round($stockAnterior + $cambio, 3);
                    if ($nuevoStock < 0) {
                        throw ValidationException::withMessages([
                            'cantidad' => "No hay suficiente stock de {$bloqueada->nombre}: hay {$stockAnterior} {$bloqueada->unidad_medida}.",
                        ]);
                    }
                } else {
                    $nuevoStock = (float) $request->nuevo_stock;
                }
                $diferencia = $nuevoStock - $stockAnterior;

                if ($diferencia > 0) {
                    $tipoMovimiento = $request->motivo === 'devolucion' ? 'entrada_devolucion' : 'entrada_ajuste';
                } else {
                    $tipoMovimiento = $request->motivo === 'merma' ? 'salida_merma' : 'salida_ajuste';
                }

                // Actualizar stock
                $bloqueada->update([
                    'stock_actual' => $nuevoStock
                ]);

                // Registrar movimiento
                $bloqueada->movimientos()->create([
                    'tipo_movimiento' => $tipoMovimiento,
                    'cantidad' => abs($diferencia),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $nuevoStock,
                    'user_id' => Auth::id(),
                    'observaciones' => "Motivo: {$request->motivo}" . ($request->observaciones ? ". {$request->observaciones}" : '')
                ]);

                return $bloqueada->fresh();
            });

            return response()->json([
                'message' => 'Stock ajustado exitosamente',
                'data' => $result
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => MensajeError::publico($e, 'Error al ajustar stock')
            ], 500);
        }
    }

    /**
     * Historial de movimientos
     */
    public function movimientos(Request $request, $id)
    {
        $query = MateriaPrima::findOrFail($id)
            ->movimientos()
            ->with(['user:id,name', 'produccion:id,fecha_produccion']);

        // Filtros
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
            ->paginate($this->porPagina($request, 20));

        return response()->json($movimientos);
    }
}
