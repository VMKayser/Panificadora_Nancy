<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCategoriaController extends Controller
{
    /**
     * Listar todas las categorías
     */
    public function index(Request $request)
    {
        $query = Categoria::withCount('productos');

        if ($request->has('activo')) {
            $query->where('esta_activo', $request->activo);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $categorias = $query->orderBy('order')->get();

        return response()->json($categorias);
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|string',
            'esta_activo' => 'boolean',
            'order' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Generar URL única
        $baseUrl = Str::slug($request->nombre);
        $url = $baseUrl;
        $counter = 1;
        
        while (Categoria::where('url', $url)->exists()) {
            $url = $baseUrl . '-' . $counter;
            $counter++;
        }
        
        $data['url'] = $url;

        // Si no se especifica orden, poner al final
        if (!isset($data['order'])) {
            $data['order'] = Categoria::max('order') + 1;
        }

        $categoria = Categoria::create($data);

        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'data' => $categoria
        ], 201);
    }

    /**
     * Ver categoría específica
     */
    public function show($id)
    {
        $categoria = Categoria::withCount('productos')->findOrFail($id);
        return response()->json($categoria);
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:100|unique:categorias,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|string',
            'esta_activo' => 'boolean',
            'order' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Si cambia el nombre, actualizar URL
        if (isset($data['nombre']) && $data['nombre'] !== $categoria->nombre) {
            $baseUrl = Str::slug($data['nombre']);
            $url = $baseUrl;
            $counter = 1;
            
            while (Categoria::where('url', $url)->where('id', '!=', $id)->exists()) {
                $url = $baseUrl . '-' . $counter;
                $counter++;
            }
            
            $data['url'] = $url;
        }

        $categoria->update($data);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'data' => $categoria->fresh()
        ]);
    }

    /**
     * Eliminar categoría
     */
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);

        // Verificar si tiene productos
        if ($categoria->productos()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar. La categoría tiene productos asociados.',
                'productos_count' => $categoria->productos()->count()
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }

    /**
     * Activar/Desactivar categoría
     */
    public function toggleActive($id)
    {
        $categoria = Categoria::findOrFail($id);
        
        $categoria->update([
            'esta_activo' => !$categoria->esta_activo
        ]);

        return response()->json([
            'message' => $categoria->esta_activo ? 'Categoría activada' : 'Categoría desactivada',
            'data' => $categoria
        ]);
    }

    /**
     * Reordenar categorías
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorias' => 'required|array',
            'categorias.*.id' => 'required|exists:categorias,id',
            'categorias.*.order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->categorias as $item) {
            Categoria::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'message' => 'Orden actualizado exitosamente'
        ]);
    }
}
