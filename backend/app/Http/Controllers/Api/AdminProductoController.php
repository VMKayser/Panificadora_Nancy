<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Producto;
use App\Models\ImagenProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\InventarioProductoFinal;
use App\Support\SafeTransaction;

class AdminProductoController extends Controller
{
    /**
     * Listar todos los productos (incluyendo inactivos)
     */
    public function index(Request $request)
    {
        // Eager-load category, inventario and only primary image to reduce payload and avoid N+1
        $query = Producto::with([
            'categoria:id,nombre',
            'inventario:producto_id,stock_actual,stock_minimo,costo_promedio',
            'imagenes' => function($q) { $q->select('id','producto_id','url_imagen','es_imagen_principal')->orderBy('order'); }
        ])->select(['id','nombre','descripcion_corta','precio_minorista','precio_mayorista','categorias_id','url','esta_activo','created_at']);

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

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        // If first page and no filters, cache the result briefly
        $shouldCache = $request->get('page', 1) == 1 && !$request->has('search') && !$request->has('categoria_id') && !$request->has('activo');
        if ($shouldCache) {
            $cacheKey = "productos.index.page.1.per.{$perPage}";
            $productos = Cache::remember($cacheKey, 30, function() use ($query, $perPage) {
                return $query->orderBy('created_at', 'desc')->paginate($perPage);
            });
        } else {
            $productos = $query->orderBy('created_at', 'desc')->paginate($perPage);
        }

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
            // Values must match the DB enum exactly to avoid SQL truncation warnings
            'unidad_medida' => 'nullable|in:unidad,cm,docena,paquete,gramos,kilogramos,arroba,porcion',
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
            $producto = SafeTransaction::run(function () use ($validated) {
                // Generar URL única
                $url = Str::slug($validated['nombre']);
                $originalUrl = $url;
                $counter = 1;
                
                while (Producto::where('url', $url)->exists()) {
                    $url = $originalUrl . '-' . $counter;
                    $counter++;
                }

                $validated['url'] = $url;

                // Ensure database-required numeric fields have defaults if not provided
                if (!isset($validated['limite_produccion'])) {
                    $validated['limite_produccion'] = 0;
                }
                if (!isset($validated['cantidad'])) {
                    $validated['cantidad'] = 0;
                }

                // Normalize boolean checkboxes: if not present in request, set sensible defaults
                $validated['es_de_temporada'] = $validated['es_de_temporada'] ?? false;
                $validated['esta_activo'] = $validated['esta_activo'] ?? true;
                // DB default for permite_delivery is true
                $validated['permite_delivery'] = $validated['permite_delivery'] ?? true;
                $validated['permite_envio_nacional'] = $validated['permite_envio_nacional'] ?? false;
                $validated['requiere_tiempo_anticipacion'] = $validated['requiere_tiempo_anticipacion'] ?? false;
                $validated['tiene_extras'] = $validated['tiene_extras'] ?? false;

                // Normalize extras: if tiene_extras is false, store null; if true but array is missing, store empty array
                if (!$validated['tiene_extras']) {
                    $validated['extras_disponibles'] = null;
                } else {
                    $validated['extras_disponibles'] = isset($validated['extras_disponibles']) ? array_values($validated['extras_disponibles']) : [];
                }

                // Crear producto
                // Create product without attempting to set productos.cantidad (we use inventory table as source of truth)
                $productoData = $validated;
                // Remove cantidad if present to avoid writing to productos.cantidad
                if (array_key_exists('cantidad', $productoData)) {
                    unset($productoData['cantidad']);
                }
                $producto = Producto::create($productoData);

                // Agregar imágenes si existen
                if (isset($validated['imagenes']) && is_array($validated['imagenes'])) {
                    foreach ($validated['imagenes'] as $index => $imagenUrl) {
                        // Security: accept only http(s) URLs or data URIs. Skip otherwise.
                        if (is_string($imagenUrl) && (preg_match('#^https?://#i', $imagenUrl) || preg_match('#^data:image/#i', $imagenUrl))) {
                            ImagenProducto::create([
                                'producto_id' => $producto->id,
                                'url_imagen' => $imagenUrl,
                                'es_imagen_principal' => $index === 0,
                                'order' => $index + 1,
                            ]);
                        } else {
                            Log::warning('Imagen no válida omitida al crear producto ' . $producto->id . ': ' . json_encode($imagenUrl));
                        }
                    }
                }

                return $producto;
            });

            // Sincronizar inventario: si se proporcionó 'cantidad' la guardamos en inventario (fuera de la transacción)
            try {
                if (isset($validated['cantidad'])) {
                    // Use query builder to avoid model events / nested savepoints
                    InventarioProductoFinal::query()->updateOrInsert(
                        ['producto_id' => $producto->id],
                        [
                            'stock_actual' => $validated['cantidad'],
                            'stock_minimo' => 0,
                            'costo_promedio' => $producto->precio_minorista ?? 0,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // No bloquear la creación del producto si falla la sincronización de inventario
                Log::warning('No se pudo sincronizar inventario para producto ' . $producto->id . ': ' . $e->getMessage());
            }

            // Invalidate caches that may be affected
            Cache::forget('productos.stats');
            foreach ([20,50,100] as $pp) {
                Cache::forget("productos.index.page.1.per.{$pp}");
            }

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'producto' => $producto->load(['categoria', 'imagenes', 'inventario'])
            ], 201);

        } catch (\Exception $e) {
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
        $producto = Producto::with(['categoria', 'imagenes', 'inventario'])->findOrFail($id);
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
            'unidad_medida' => 'nullable|in:unidad,cm,docena,paquete,gramos,kilogramos,arroba,porcion',
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
            $productoUpdated = SafeTransaction::run(function () use ($validated, $producto, $id) {
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

                // Avoid writing to productos.cantidad (inventory is source of truth)
                if (array_key_exists('cantidad', $dataToUpdate)) {
                    unset($dataToUpdate['cantidad']);
                }

                // Ensure boolean defaults when checkboxes are omitted from the payload
                $defaults = [
                    'es_de_temporada' => false,
                    'esta_activo' => true,
                    'permite_delivery' => false,
                    'permite_envio_nacional' => false,
                    'requiere_tiempo_anticipacion' => false,
                    'tiene_extras' => false,
                ];
                foreach ($defaults as $k => $v) {
                    if (!array_key_exists($k, $dataToUpdate)) {
                        // if key was not provided, we don't want to overwrite existing value; only set if explicitly present in validated
                        if (array_key_exists($k, $validated)) {
                            $dataToUpdate[$k] = $validated[$k];
                        }
                    }
                }

                // Normalize extras for update: if tiene_extras is false, set extras_disponibles to null
                if (array_key_exists('tiene_extras', $dataToUpdate) && $dataToUpdate['tiene_extras'] === false) {
                    $dataToUpdate['extras_disponibles'] = null;
                } elseif (array_key_exists('tiene_extras', $dataToUpdate) && $dataToUpdate['tiene_extras'] === true) {
                    $dataToUpdate['extras_disponibles'] = array_key_exists('extras_disponibles', $validated) ? array_values($validated['extras_disponibles']) : [];
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

                return $producto;
            });

            // Invalidate caches
            Cache::forget('productos.stats');
            foreach ([20,50,100] as $pp) {
                Cache::forget("productos.index.page.1.per.{$pp}");
            }

            // Sincronizar inventario si se envió 'cantidad' (hacerlo después del commit para no interferir con la transacción)
            try {
                if (array_key_exists('cantidad', $validated)) {
                    InventarioProductoFinal::query()->updateOrInsert(
                        ['producto_id' => $producto->id],
                        [
                            'stock_actual' => $validated['cantidad'] ?? 0,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo sincronizar inventario (update) para producto ' . $producto->id . ': ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'producto' => $producto->fresh(['categoria', 'imagenes', 'inventario'])
            ]);

        } catch (\Exception $e) {
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

        // Invalidate caches
        Cache::forget('productos.stats');
        foreach ([20,50,100] as $pp) {
            Cache::forget("productos.index.page.1.per.{$pp}");
        }

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

        // Invalidate caches
        Cache::forget('productos.stats');
        foreach ([20,50,100] as $pp) {
            Cache::forget("productos.index.page.1.per.{$pp}");
        }

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

        // Invalidate caches
        Cache::forget('productos.stats');
        foreach ([20,50,100] as $pp) {
            Cache::forget("productos.index.page.1.per.{$pp}");
        }

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
        // Cache this because stats are expensive and change slowly; short TTL
        return Cache::remember('productos.stats', 60, function() {
            // Use efficient aggregated queries
            $total = Producto::count();
            $activos = Producto::where('esta_activo', true)->count();
            $temporada = Producto::where('es_de_temporada', true)->count();
            $sinImagen = Producto::doesntHave('imagenes')->count();
            $porCategoria = Producto::select('categorias_id', DB::raw('count(*) as total'))
                ->groupBy('categorias_id')
                ->get();

            // Resolve category names in a separate query to avoid loading full producto models
            $categoriaIds = $porCategoria->pluck('categorias_id')->filter()->unique()->values();
            $categorias = DB::table('categorias')->whereIn('id', $categoriaIds)->pluck('nombre','id');

            $por_categoria = $porCategoria->map(function($item) use ($categorias) {
                return [
                    'categoria' => $categorias[$item->categorias_id] ?? 'Sin categoría',
                    'total' => $item->total,
                ];
            })->values();

            return [
                'total_productos' => $total,
                'productos_activos' => $activos,
                'productos_temporada' => $temporada,
                'productos_sin_imagen' => $sinImagen,
                'por_categoria' => $por_categoria,
            ];
        });

        return response()->json($stats);
    }
}
