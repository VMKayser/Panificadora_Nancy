<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Panadero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PanaderoController extends Controller
{
    /**
     * Listar todos los panaderos
     */
    public function index(Request $request)
    {
        $query = Panadero::with('user');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('turno')) {
            $query->where('turno', $request->turno);
        }

        if ($request->has('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('ci', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $panaderos = $query->paginate($perPage);

        return response()->json($panaderos);
    }

    /**
     * Mostrar un panadero específico
     */
    public function show($id)
    {
        $panadero = Panadero::with('producciones')->findOrFail($id);
        
        return response()->json($panadero);
    }

    /**
     * Crear un nuevo panadero
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:panaderos,email',
            'telefono' => 'required|string|max:20',
            'ci' => 'required|string|max:20|unique:panaderos,ci',
            'direccion' => 'nullable|string',
            'fecha_ingreso' => 'required|date',
            'turno' => 'required|in:mañana,tarde,noche,rotativo',
            'especialidad' => 'required|in:pan,reposteria,ambos',
            'salario_base' => 'required|numeric|min:0',
            'salario_por_kilo' => 'sometimes|numeric|min:0',
            'observaciones' => 'nullable|string'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.unique' => 'Ya existe un panadero con este email',
            'ci.required' => 'El CI es obligatorio',
            'ci.unique' => 'Ya existe un panadero con este CI',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria',
            'salario_base.required' => 'El salario base es obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $panadero = Panadero::create($request->all());

        return response()->json([
            'message' => 'Panadero creado exitosamente',
            'panadero' => $panadero
        ], 201);
    }

    /**
     * Actualizar un panadero existente
     */
    public function update(Request $request, $id)
    {
        $panadero = Panadero::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:100',
            'apellido' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:150|unique:panaderos,email,' . $id,
            'telefono' => 'sometimes|required|string|max:20',
            'ci' => 'sometimes|required|string|max:20|unique:panaderos,ci,' . $id,
            'direccion' => 'nullable|string',
            'fecha_ingreso' => 'sometimes|required|date',
            'turno' => 'sometimes|required|in:mañana,tarde,noche,rotativo',
            'especialidad' => 'sometimes|required|in:pan,reposteria,ambos',
            'salario_base' => 'sometimes|required|numeric|min:0',
            'salario_por_kilo' => 'sometimes|numeric|min:0',
            'activo' => 'sometimes|boolean',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $panadero->update($request->all());

        return response()->json([
            'message' => 'Panadero actualizado exitosamente',
            'panadero' => $panadero
        ]);
    }

    /**
     * Eliminar (soft delete) un panadero
     */
    public function destroy($id)
    {
        $panadero = Panadero::findOrFail($id);
        $panadero->delete();

        return response()->json([
            'message' => 'Panadero eliminado exitosamente'
        ]);
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActivo($id)
    {
        $panadero = Panadero::findOrFail($id);
        $panadero->activo = !$panadero->activo;
        $panadero->save();

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'panadero' => $panadero
        ]);
    }

    /**
     * Obtener estadísticas de panaderos
     */
    public function estadisticas()
    {
        $stats = [
            'total_panaderos' => Panadero::count(),
            'panaderos_activos' => Panadero::where('activo', true)->count(),
            'panaderos_inactivos' => Panadero::where('activo', false)->count(),
            'por_turno' => [
                'mañana' => Panadero::where('turno', 'mañana')->where('activo', true)->count(),
                'tarde' => Panadero::where('turno', 'tarde')->where('activo', true)->count(),
                'noche' => Panadero::where('turno', 'noche')->where('activo', true)->count(),
                'rotativo' => Panadero::where('turno', 'rotativo')->where('activo', true)->count(),
            ],
            'por_especialidad' => [
                'pan' => Panadero::where('especialidad', 'pan')->where('activo', true)->count(),
                'reposteria' => Panadero::where('especialidad', 'reposteria')->where('activo', true)->count(),
                'ambos' => Panadero::where('especialidad', 'ambos')->where('activo', true)->count(),
            ],
            'total_kilos_producidos' => Panadero::sum('total_kilos_producidos'),
            'salario_total_mensual' => Panadero::where('activo', true)->sum('salario_base')
        ];

        return response()->json($stats);
    }
}
