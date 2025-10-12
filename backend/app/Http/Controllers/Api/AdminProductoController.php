<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ImagenProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminProductoController extends Controller
{
    /**
     * Listar todos los productos (incluyendo inactivos)
     */
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'imagenes']);

        // Filtros opcionales
        if ($request->has('categoria_id')) {
            $query->where('categorias_id', $request->categoria_id);
        }

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

        $productos = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($productos);
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorias_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'descripcion_corta' => 'nullable|string',
            'precio_minorista' => 'required|numeric|min:0',
            'precio_mayorista' => 'nullable|numeric|min:0',
            'cantidad_minima_mayoreo' => 'nullable|integer|min:1',
            'unidad_medida' => 'nullable|in:unidad,docena,kilo,gramo,litro,mililitro,paquete,arroba',
            'cantidad' => 'nullable|numeric|min:0',
            'presentacion' => 'nullable|string',
            'es_de_temporada' => 'boolean',
            'esta_activo' => 'boolean',
            'permite_delivery' => 'boolean',
            'permite_envio_nacional' => 'boolean',
            'requiere_tiempo_anticipacion' => 'boolean',
            'tiempo_anticipacion' => 'nullable|integer|min:0',
            'unidad_tiempo' => 'nullable|in:horas,dias,semanas',
            'limite_produccion' => 'nullable|integer|min:0',
            'tiene_extras' => 'boolean',
            'extras_disponibles' => 'nullable|array',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'nullable|string', // Puede ser URL o base64
        ]);

        try {
            DB::beginTransaction();

            // Generar URL única
            $url = Str::slug($validated['nombre']);
            $originalUrl = $url;
            $counter = 1;
            
            while (Producto::where('url', $url)->exists()) {
                $url = $originalUrl . '-' . $counter;
                $counter++;
            }

            $validated['url'] = $url;

            // Crear producto
            $producto = Producto::create($validated);

            // Agregar imágenes si existen
            if (isset($validated['imagenes']) && is_array($validated['imagenes'])) {
                foreach ($validated['imagenes'] as $index => $imagenUrl) {
                    ImagenProducto::create([
                        'producto_id' => $producto->id,
                        'url_imagen' => $imagenUrl,
                        'es_imagen_principal' => $index === 0,
                        'order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'producto' => $producto->load(['categoria', 'imagenes'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un producto específico
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'imagenes'])->findOrFail($id);
        return response()->json($producto);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        // Debug: Log de entrada
        Log::info('=== INICIO UPDATE PRODUCTO ===', [
            'id' => $id,
            'request_data' => $request->all()
        ]);

        $producto = Producto::findOrFail($id);

        $validated = $request->validate([
            'categorias_id' => 'nullable|exists:categorias,id',
            'nombre' => 'nullable|string|max:150',
            'descripcion' => 'nullable|string',
            'descripcion_corta' => 'nullable|string',
            'precio_minorista' => 'nullable|numeric|min:0',
            'precio_mayorista' => 'nullable|numeric|min:0',
            'cantidad_minima_mayoreo' => 'nullable|integer|min:1',
            'unidad_medida' => 'nullable|in:unidad,docena,kilo,gramo,litro,mililitro,paquete,arroba',
            'cantidad' => 'nullable|numeric|min:0',
            'presentacion' => 'nullable|string',
            'es_de_temporada' => 'nullable|boolean',
            'esta_activo' => 'nullable|boolean',
            'permite_delivery' => 'nullable|boolean',
            'permite_envio_nacional' => 'nullable|boolean',
            'requiere_tiempo_anticipacion' => 'nullable|boolean',
            'tiempo_anticipacion' => 'nullable|integer|min:0',
            'unidad_tiempo' => 'nullable|in:horas,dias,semanas',
            'limite_produccion' => 'nullable|integer|min:0',
            'tiene_extras' => 'nullable|boolean',
            'extras_disponibles' => 'nullable|array',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'nullable|string', // Puede ser URL o base64
        ]);

        Log::info('Datos validados', ['validated' => $validated]);

        try {
            DB::beginTransaction();

            // Filtrar solo los campos que fueron enviados (pero mantener arrays vacíos y false)
            $dataToUpdate = array_filter($validated, function($value, $key) {
                // Mantener arrays vacíos, booleanos false, y valores no nulos
                if (is_array($value)) {
                    return true; // Mantener todos los arrays, incluso vacíos
                }
                if (is_bool($value)) {
                    return true; // Mantener valores booleanos
                }
                return $value !== null; // Filtrar solo valores null
            }, ARRAY_FILTER_USE_BOTH);

            // Si cambia el nombre, regenerar URL
            if (isset($dataToUpdate['nombre']) && $dataToUpdate['nombre'] !== $producto->nombre) {
                $url = Str::slug($dataToUpdate['nombre']);
                $originalUrl = $url;
                $counter = 1;
                
                while (Producto::where('url', $url)->where('id', '!=', $id)->exists()) {
                    $url = $originalUrl . '-' . $counter;
                    $counter++;
                }
                
                $dataToUpdate['url'] = $url;
            }

            // Actualizar solo los campos proporcionados
            if (!empty($dataToUpdate)) {
                $producto->update($dataToUpdate);
            }

            // Actualizar imágenes si se proporcionan
            if (isset($validated['imagenes']) && is_array($validated['imagenes'])) {
                // Eliminar imágenes antiguas
                ImagenProducto::where('producto_id', $producto->id)->delete();
                
                // Agregar nuevas imágenes
                foreach ($validated['imagenes'] as $index => $imagenUrl) {
                    ImagenProducto::create([
                        'producto_id' => $producto->id,
                        'url_imagen' => $imagenUrl,
                        'es_imagen_principal' => $index === 0,
                        'order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'producto' => $producto->fresh(['categoria', 'imagenes'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar producto', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar producto (soft delete)
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }

    /**
     * Restaurar producto eliminado
     */
    public function restore($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->restore();

        return response()->json([
            'message' => 'Producto restaurado exitosamente',
            'producto' => $producto->load(['categoria', 'imagenes'])
        ]);
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleActive($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->esta_activo = !$producto->esta_activo;
        $producto->save();

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'producto' => $producto
        ]);
    }

    /**
     * Upload de imagen con validaciones de seguridad
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // Máximo 5MB
                'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            ],
        ], [
            'image.dimensions' => 'La imagen debe tener un mínimo de 200x200px y un máximo de 4000x4000px.',
            'image.max' => 'La imagen no puede superar los 5MB.',
        ]);

        try {
            $image = $request->file('image');
            
            // Nombre seguro usando hash
            $extension = $image->getClientOriginalExtension();
            $filename = hash('sha256', time() . Str::random(20)) . '.' . $extension;
            
            // Asegurar que el directorio existe
            if (!Storage::disk('public')->exists('productos')) {
                Storage::disk('public')->makeDirectory('productos');
            }
            
            // Guardar la imagen
            $path = $image->storeAs('productos', $filename, 'public');

            // URL completa accesible desde el frontend
            // Usar config('app.url') para asegurar la URL correcta
            $url = config('app.url') . Storage::url($path);
            
            Log::info('Imagen subida', [
                'filename' => $filename,
                'path' => $path,
                'url' => $url,
                'app_url' => config('app.url')
            ]);

            return response()->json([
                'message' => 'Imagen subida exitosamente',
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error('Error al subir imagen', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al subir imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas del catálogo
     */
    public function stats()
    {
        $stats = [
            'total_productos' => Producto::count(),
            'productos_activos' => Producto::where('esta_activo', true)->count(),
            'productos_temporada' => Producto::where('es_de_temporada', true)->count(),
            'productos_sin_imagen' => Producto::doesntHave('imagenes')->count(),
            'por_categoria' => Producto::select('categorias_id', DB::raw('count(*) as total'))
                ->groupBy('categorias_id')
                ->with('categoria:id,nombre')
                ->get()
                ->map(function($item) {
                    return [
                        'categoria' => $item->categoria->nombre ?? 'Sin categoría',
                        'total' => $item->total
                    ];
                }),
        ];

        return response()->json($stats);
    }
}
