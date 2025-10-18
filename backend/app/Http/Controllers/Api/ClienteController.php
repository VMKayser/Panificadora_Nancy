<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class ClienteController extends Controller
{
    /**
     * Lista de clientes con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        // Filtro por tipo de cliente
        if ($request->has('tipo_cliente') && $request->tipo_cliente !== 'todos') {
            $query->where('tipo_cliente', $request->tipo_cliente);
        }

        // Filtro por estado activo
        if ($request->has('activo')) {
            $query->where('activo', $request->activo === 'true' || $request->activo === '1');
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $clientes = $query->with('pedidos')->paginate($perPage);

        return response()->json($clientes);
    }

    /**
     * Crear un nuevo cliente
     */
    public function store(Request $request)
    {
        // Build ci rule: if panaderos/clientes table has ci, we keep; else if users has ci we avoid unique on clientes
        $ciRule = ['nullable','string','max:20'];
        if (Schema::hasColumn('clientes', 'ci')) {
            $ciRule = ['nullable','string','max:20'];
        } elseif (Schema::hasColumn('users', 'ci')) {
            // store ci on users instead of clientes; make it optional here
            $ciRule = ['nullable','string','max:50'];
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            // apellido can be optional; if not provided we'll default to empty string
            'apellido' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clientes,email',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'ci' => $ciRule,
            'tipo_cliente' => 'required|in:regular,mayorista,vip',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
        ]);

    $validated['activo'] = $validated['activo'] ?? true;
    $validated['apellido'] = $validated['apellido'] ?? '';

    $cliente = Cliente::create($validated);

        return response()->json([
            'message' => 'Cliente creado exitosamente',
            'cliente' => $cliente
        ], 201);
    }

    /**
     * Mostrar un cliente específico
     */
    public function show($id)
    {
        $cliente = Cliente::with('pedidos.detalles.producto')->findOrFail($id);
        
        return response()->json($cliente);
    }

    /**
     * Actualizar un cliente
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('clientes')->ignore($cliente->id)
            ],
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'ci' => 'nullable|string|max:20',
            'tipo_cliente' => 'required|in:regular,mayorista,vip',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $cliente->update($validated);

        return response()->json([
            'message' => 'Cliente actualizado exitosamente',
            'cliente' => $cliente
        ]);
    }

    /**
     * Eliminar (soft delete) un cliente
     */
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }

    /**
     * Buscar cliente por email (para checkout)
     */
    public function findByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $cliente = Cliente::where('email', $request->email)->first();

        if (!$cliente) {
            return response()->json([
                'found' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'found' => true,
            'cliente' => $cliente
        ]);
    }

    /**
     * Estadísticas de clientes
     */
    public function estadisticas()
    {
        $total = Cliente::count();
        $activos = Cliente::where('activo', true)->count();
        $porTipo = Cliente::selectRaw('tipo_cliente, COUNT(*) as total')
            ->groupBy('tipo_cliente')
            ->get()
            ->pluck('total', 'tipo_cliente');

        $topClientes = Cliente::where('activo', true)
            ->orderBy('total_gastado', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $total - $activos,
            'regulares' => $porTipo['regular'] ?? 0,
            'mayoristas' => $porTipo['mayorista'] ?? 0,
            'vip' => $porTipo['vip'] ?? 0,
            'top_clientes' => $topClientes
        ]);
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleActive($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->activo = !$cliente->activo;
        $cliente->save();

        return response()->json([
            'message' => 'Estado del cliente actualizado',
            'cliente' => $cliente
        ]);
    }
}
