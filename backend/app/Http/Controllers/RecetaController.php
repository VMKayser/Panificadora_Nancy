<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\Producto;
use App\Models\MateriaPrima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RecetaController extends Controller
{
    /**
     * Listar todas las recetas
     */
    public function index(Request $request)
    {
        $query = Receta::with(['producto', 'ingredientes.materiaPrima']);

        // Filtros
        if ($request->has('activa')) {
            $query->where('activa', $request->activa);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_receta', 'like', "%{$search}%")
                  ->orWhereHas('producto', function ($pq) use ($search) {
                      $pq->where('nombre', 'like', "%{$search}%");
                  });
            });
        }

        $recetas = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($recetas);
    }

    /**
     * Crear nueva receta
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'nombre_receta' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'rendimiento' => 'required|numeric|min:0.001',
            'unidad_rendimiento' => 'required|in:unidades,kg,docenas',
            'ingredientes' => 'required|array|min:1',
            'ingredientes.*.materia_prima_id' => 'required|exists:materias_primas,id',
            'ingredientes.*.cantidad' => 'required|numeric|min:0.001',
            'ingredientes.*.unidad' => 'required|in:kg,g,L,ml,unidades',
            'ingredientes.*.orden' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Crear receta
            $receta = Receta::create([
                'producto_id' => $request->producto_id,
                'nombre_receta' => $request->nombre_receta,
                'descripcion' => $request->descripcion,
                'rendimiento' => $request->rendimiento,
                'unidad_rendimiento' => $request->unidad_rendimiento,
                'activa' => true,
                'version' => 1
            ]);

            // Agregar ingredientes
            foreach ($request->ingredientes as $index => $ingrediente) {
                $receta->ingredientes()->create([
                    'materia_prima_id' => $ingrediente['materia_prima_id'],
                    'cantidad' => $ingrediente['cantidad'],
                    'unidad' => $ingrediente['unidad'],
                    'orden' => $ingrediente['orden'] ?? ($index + 1)
                ]);
            }

            // Calcular costos
            $receta->calcularCostos();

            DB::commit();

            return response()->json([
                'message' => 'Receta creada exitosamente',
                'data' => $receta->load(['ingredientes.materiaPrima', 'producto'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear receta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar receta específica
     */
    public function show($id)
    {
        $receta = Receta::with([
            'producto',
            'ingredientes.materiaPrima',
            'ingredientes' => function ($query) {
                $query->orderBy('orden', 'asc');
            }
        ])->findOrFail($id);

    // Agregar información adicional (comprobar si se puede producir al menos 1 unidad)
    $verificacion = $receta->verificarStock(1);
    $receta->puede_producir = is_array($verificacion) ? ($verificacion['tiene_stock'] ?? false) : (bool)$verificacion;
        
        return response()->json($receta);
    }

    /**
     * Actualizar receta
     */
    public function update(Request $request, $id)
    {
        $receta = Receta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre_receta' => 'sometimes|required|string|max:200',
            'descripcion' => 'nullable|string',
            'rendimiento' => 'sometimes|required|numeric|min:0.001',
            'unidad_rendimiento' => 'sometimes|required|in:unidades,kg,docenas',
            'activa' => 'boolean',
            'ingredientes' => 'sometimes|required|array|min:1',
            'ingredientes.*.materia_prima_id' => 'required|exists:materias_primas,id',
            'ingredientes.*.cantidad' => 'required|numeric|min:0.001',
            'ingredientes.*.unidad' => 'required|in:kg,g,L,ml,unidades',
            'ingredientes.*.orden' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Si hay cambios en ingredientes, crear nueva versión
            if ($request->has('ingredientes')) {
                $receta->update(['version' => $receta->version + 1]);
                
                // Eliminar ingredientes anteriores
                $receta->ingredientes()->delete();
                
                // Agregar nuevos ingredientes
                foreach ($request->ingredientes as $index => $ingrediente) {
                    $receta->ingredientes()->create([
                        'materia_prima_id' => $ingrediente['materia_prima_id'],
                        'cantidad' => $ingrediente['cantidad'],
                        'unidad' => $ingrediente['unidad'],
                        'orden' => $ingrediente['orden'] ?? ($index + 1)
                    ]);
                }
            }

            // Actualizar otros campos
            $receta->update($request->except(['ingredientes', 'producto_id']));

            // Recalcular costos
            $receta->calcularCostos();

            DB::commit();

            return response()->json([
                'message' => 'Receta actualizada exitosamente',
                'data' => $receta->load(['ingredientes.materiaPrima', 'producto'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar receta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar receta
     */
    public function destroy($id)
    {
        $receta = Receta::findOrFail($id);
        
        // Verificar si hay producciones asociadas
        $tieneProducciones = $receta->producciones()->exists();

        if ($tieneProducciones) {
            // Solo desactivar, no eliminar
            $receta->update(['activa' => false]);
            
            return response()->json([
                'message' => 'Receta desactivada (tiene producciones registradas)'
            ]);
        }

        // Si no tiene producciones, se puede eliminar
        $receta->delete();

        return response()->json([
            'message' => 'Receta eliminada exitosamente'
        ]);
    }

    /**
     * Verificar disponibilidad de stock para producir
     */
    public function verificarStock($id, Request $request)
    {
        $receta = Receta::findOrFail($id);
        
        $cantidad = $request->get('cantidad', 1);
        
        $disponibilidad = $receta->verificarStock($cantidad);

        // Mapear ingredientes y calcular cantidades necesarias (con conversiones básicas)
        $ingredientes = $receta->ingredientes->map(function ($ingrediente) use ($cantidad, $receta) {
            $materia = $ingrediente->materiaPrima;

            // Cantidad proporcional según rendimiento de la receta
            $cantidadNecesaria = round(($ingrediente->cantidad / $receta->rendimiento) * $cantidad, 3);

            // Si las unidades difieren, aplicar conversiones simples (g<->kg, ml<->L)
            $cantidadEnBase = $cantidadNecesaria;
            if ($materia && $ingrediente->unidad !== $materia->unidad_medida) {
                // peso
                if ($ingrediente->unidad === 'g' && $materia->unidad_medida === 'kg') {
                    $cantidadEnBase = $cantidadNecesaria / 1000;
                } elseif ($ingrediente->unidad === 'kg' && $materia->unidad_medida === 'g') {
                    $cantidadEnBase = $cantidadNecesaria * 1000;
                }
                // volumen
                elseif ($ingrediente->unidad === 'ml' && $materia->unidad_medida === 'L') {
                    $cantidadEnBase = $cantidadNecesaria / 1000;
                } elseif ($ingrediente->unidad === 'L' && $materia->unidad_medida === 'ml') {
                    $cantidadEnBase = $cantidadNecesaria * 1000;
                }
            }

            return [
                'nombre' => $materia?->nombre ?? 'N/D',
                'cantidad_necesaria' => $cantidadNecesaria,
                'unidad' => $ingrediente->unidad,
                'stock_disponible' => $materia?->stock_actual ?? 0,
                'unidad_stock' => $materia?->unidad_medida ?? null,
                'suficiente' => $materia ? ($materia->stock_actual >= $cantidadEnBase) : false
            ];
        });

        return response()->json([
            'puede_producir' => $disponibilidad,
            'cantidad_solicitada' => $cantidad,
            'rendimiento_receta' => $receta->rendimiento,
            'ingredientes' => $ingredientes
        ]);
    }

    /**
     * Recalcular costos de la receta
     */
    public function recalcularCostos($id)
    {
        $receta = Receta::findOrFail($id);

        try {
            $receta->calcularCostos();

            return response()->json([
                'message' => 'Costos recalculados exitosamente',
                'data' => [
                    'costo_total' => $receta->costo_total_calculado,
                    'costo_unitario' => $receta->costo_unitario_calculado,
                    'rendimiento' => $receta->rendimiento
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al recalcular costos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar receta (para crear variantes)
     */
    public function duplicar($id, Request $request)
    {
        $recetaOriginal = Receta::with('ingredientes')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre_receta' => 'required|string|max:200',
            'producto_id' => 'nullable|exists:productos,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Crear nueva receta
            $nuevaReceta = Receta::create([
                'producto_id' => $request->producto_id ?? $recetaOriginal->producto_id,
                'nombre_receta' => $request->nombre_receta,
                'descripcion' => $recetaOriginal->descripcion,
                'rendimiento' => $recetaOriginal->rendimiento,
                'unidad_rendimiento' => $recetaOriginal->unidad_rendimiento,
                'activa' => false, // Crear desactivada para revisión
                'version' => 1
            ]);

            // Copiar ingredientes
            foreach ($recetaOriginal->ingredientes as $ingrediente) {
                $nuevaReceta->ingredientes()->create([
                    'materia_prima_id' => $ingrediente->materia_prima_id,
                    'cantidad' => $ingrediente->cantidad,
                    'unidad' => $ingrediente->unidad,
                    'orden' => $ingrediente->orden
                ]);
            }

            // Calcular costos
            $nuevaReceta->calcularCostos();

            DB::commit();

            return response()->json([
                'message' => 'Receta duplicada exitosamente',
                'data' => $nuevaReceta->load(['ingredientes.materiaPrima', 'producto'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al duplicar receta: ' . $e->getMessage()
            ], 500);
        }
    }
}
