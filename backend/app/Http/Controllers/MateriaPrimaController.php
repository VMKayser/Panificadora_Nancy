<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Support\SafeTransaction;

class MateriaPrimaController extends Controller
{
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
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $materiasPrimas = $query->paginate($request->get('per_page', 15));

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
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric|min:0.001',
            'costo_unitario' => 'required|numeric|min:0',
            'numero_factura' => 'nullable|string|max:100',
            'observaciones' => 'string'
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

                return $materiaPrima->fresh();
            });

            return response()->json([
                'message' => 'Compra registrada exitosamente',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajustar stock manualmente
     */
    public function ajustarStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nuevo_stock' => 'required|numeric|min:0',
            'motivo' => 'required|in:inventario_fisico,merma,correccion,devolucion',
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
                $stockAnterior = $materiaPrima->stock_actual;
                $diferencia = $request->nuevo_stock - $stockAnterior;
                
                $tipoMovimiento = $diferencia > 0 ? 'entrada_ajuste' : 'salida_ajuste';
                
                // Actualizar stock
                $materiaPrima->update([
                    'stock_actual' => $request->nuevo_stock
                ]);

                // Registrar movimiento
                $materiaPrima->movimientos()->create([
                    'tipo_movimiento' => $tipoMovimiento,
                    'cantidad' => abs($diferencia),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $request->nuevo_stock,
                    'user_id' => Auth::id(),
                    'observaciones' => "Motivo: {$request->motivo}" . ($request->observaciones ? ". {$request->observaciones}" : '')
                ]);

                return $materiaPrima->fresh();
            });

            return response()->json([
                'message' => 'Stock ajustado exitosamente',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al ajustar stock: ' . $e->getMessage()
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
            ->paginate($request->get('per_page', 20));

        return response()->json($movimientos);
    }
}
