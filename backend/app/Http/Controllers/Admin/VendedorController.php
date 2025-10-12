<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendedorController extends Controller
{
    /**
     * Listar todos los vendedores
     */
    public function index(Request $request)
    {
        $query = Vendedor::with('user');

        // Por defecto mostrar solo activos, a menos que se especifique explícitamente
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        } else {
            $query->where('estado', 'activo');
        }

        if ($request->has('turno')) {
            $query->where('turno', $request->turno);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('user', function($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            })->orWhere('codigo_vendedor', 'like', "%{$buscar}%");
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $vendedores = $query->paginate($perPage);

        return response()->json($vendedores);
    }

    /**
     * Mostrar un vendedor específico
     */
    public function show($id)
    {
        $vendedor = Vendedor::with(['user', 'pedidos'])->findOrFail($id);
        
        return response()->json($vendedor);
    }

    /**
     * Crear un nuevo vendedor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:vendedores,user_id',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
            'descuento_maximo_bs' => 'required|numeric|min:0',
            'puede_dar_descuentos' => 'boolean',
            'puede_cancelar_ventas' => 'boolean',
            'turno' => 'required|in:mañana,tarde,noche,rotativo',
            'fecha_ingreso' => 'required|date',
            'observaciones' => 'nullable|string'
        ], [
            'user_id.required' => 'Debe seleccionar un usuario',
            'user_id.unique' => 'Este usuario ya está registrado como vendedor',
            'comision_porcentaje.required' => 'La comisión es obligatoria',
            'turno.required' => 'El turno es obligatorio',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Generar código único de vendedor si no se proporciona
        if (!isset($data['codigo_vendedor']) || empty($data['codigo_vendedor'])) {
            $data['codigo_vendedor'] = 'VEN-' . date('Y') . '-' . str_pad(Vendedor::count() + 1, 4, '0', STR_PAD_LEFT);
        }

        $vendedor = Vendedor::create($data);

        return response()->json([
            'message' => 'Vendedor creado exitosamente',
            'vendedor' => $vendedor->load('user')
        ], 201);
    }

    /**
     * Actualizar un vendedor existente
     */
    public function update(Request $request, $id)
    {
        $vendedor = Vendedor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'comision_porcentaje' => 'sometimes|required|numeric|min:0|max:100',
            'descuento_maximo_bs' => 'sometimes|required|numeric|min:0',
            'puede_dar_descuentos' => 'sometimes|boolean',
            'puede_cancelar_ventas' => 'sometimes|boolean',
            'turno' => 'sometimes|required|in:mañana,tarde,noche,rotativo',
            'fecha_ingreso' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:activo,inactivo,suspendido',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $vendedor->update($request->all());

        return response()->json([
            'message' => 'Vendedor actualizado exitosamente',
            'vendedor' => $vendedor->load('user')
        ]);
    }

    /**
     * Eliminar (soft delete) un vendedor
     */
    public function destroy($id)
    {
        $vendedor = Vendedor::findOrFail($id);
        $vendedor->delete();

        return response()->json([
            'message' => 'Vendedor eliminado exitosamente'
        ]);
    }

    /**
     * Cambiar estado del vendedor
     */
    public function cambiarEstado(Request $request, $id)
    {
        $vendedor = Vendedor::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:activo,inactivo,suspendido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $vendedor->estado = $request->estado;
        $vendedor->save();

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'vendedor' => $vendedor
        ]);
    }

    /**
     * Obtener estadísticas de vendedores
     */
    public function estadisticas()
    {
        $stats = [
            'total_vendedores' => Vendedor::count(),
            'vendedores_activos' => Vendedor::where('estado', 'activo')->count(),
            'vendedores_inactivos' => Vendedor::where('estado', 'inactivo')->count(),
            'vendedores_suspendidos' => Vendedor::where('estado', 'suspendido')->count(),
            'por_turno' => [
                'mañana' => Vendedor::where('turno', 'mañana')->where('estado', 'activo')->count(),
                'tarde' => Vendedor::where('turno', 'tarde')->where('estado', 'activo')->count(),
                'noche' => Vendedor::where('turno', 'noche')->where('estado', 'activo')->count(),
                'rotativo' => Vendedor::where('turno', 'rotativo')->where('estado', 'activo')->count(),
            ],
            'total_ventas' => Vendedor::sum('ventas_realizadas'),
            'total_vendido' => Vendedor::sum('total_vendido'),
            'total_descuentos_otorgados' => Vendedor::sum('descuentos_otorgados'),
            'comision_promedio' => Vendedor::where('estado', 'activo')->avg('comision_porcentaje')
        ];

        return response()->json($stats);
    }

    /**
     * Obtener reporte de ventas de un vendedor
     */
    public function reporteVentas($id, Request $request)
    {
        $vendedor = Vendedor::with('user')->findOrFail($id);

        $query = $vendedor->pedidos();

        // Filtrar por rango de fechas si se proporciona
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('created_at', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        }

        $pedidos = $query->with('detalles.producto')->get();

        $reporte = [
            'vendedor' => $vendedor,
            'periodo' => [
                'inicio' => $request->fecha_inicio ?? 'Inicio',
                'fin' => $request->fecha_fin ?? 'Hoy'
            ],
            'totales' => [
                'pedidos' => $pedidos->count(),
                'total_vendido' => $pedidos->sum('total'),
                'descuentos' => $pedidos->sum('descuento_bs'),
                'comisiones' => $pedidos->sum(function($pedido) use ($vendedor) {
                    return ($pedido->total * $vendedor->comision_porcentaje) / 100;
                })
            ],
            'pedidos' => $pedidos
        ];

        return response()->json($reporte);
    }
}
