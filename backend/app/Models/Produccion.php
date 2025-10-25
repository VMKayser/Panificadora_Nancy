<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Support\SafeTransaction;

class Produccion extends Model
{
    use SoftDeletes;

    protected $table = 'producciones';

    protected $fillable = [
        'producto_id',
        'receta_id',
        'user_id',
        'fecha_produccion',
        'hora_inicio',
        'hora_fin',
        'cantidad_producida',
        'unidad',
        'harina_real_usada',
        'harina_teorica',
        'diferencia_harina',
        'tipo_diferencia',
        'costo_produccion',
        'costo_unitario',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_produccion' => 'date',
        'cantidad_producida' => 'decimal:3',
        'harina_real_usada' => 'decimal:3',
        'harina_teorica' => 'decimal:3',
        'diferencia_harina' => 'decimal:3',
        'costo_produccion' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientosMateriaPrima()
    {
        return $this->hasMany(MovimientoMateriaPrima::class);
    }

    public function movimientosProductoFinal()
    {
        return $this->hasMany(MovimientoProductoFinal::class);
    }

    /**
     * Procesar la producción: descontar ingredientes, actualizar stock producto final
     * Acepta un array opcional de ingredientes adicionales en formato:
     * [ ['materia_prima_id' => int, 'cantidad' => float], ... ]
     */
    public function procesar(array $ingredientesExtra = [])
    {
        // Validación de estado
        if ($this->estado !== 'en_proceso') {
            throw new \Exception('Solo se pueden procesar producciones en estado "en_proceso"');
        }

        // Cargar relaciones necesarias (evitar N+1)
        $receta = $this->receta->load('ingredientes.materiaPrima');

        // Validación: evitar división por cero
        if ($receta->rendimiento <= 0) {
            throw new \Exception('La receta debe tener un rendimiento mayor a 0');
        }

        // Validación: cantidad producida debe ser positiva
        if ($this->cantidad_producida <= 0) {
            throw new \Exception('La cantidad producida debe ser mayor a 0');
        }

        // INICIAR TRANSACCIÓN - CRÍTICO PARA CONSISTENCIA
    $executor = function () use ($receta, $ingredientesExtra) {
            $factor = $this->cantidad_producida / $receta->rendimiento;
            $ingredientes_faltantes = [];

            // Construir la lista completa de ingredientes a procesar: receta + extras
            $todosIngredientes = collect($receta->ingredientes->map(function ($i) use ($factor) {
                return [
                    'materia_prima' => $i->materiaPrima,
                    'cantidad_necesaria' => $i->cantidad * $factor,
                    'es_harina' => str_contains(strtolower($i->materiaPrima->nombre), 'harina'),
                ];
            }))->values();

            // Añadir ingredientes extra (materia_prima_id, cantidad) al listado
            foreach ($ingredientesExtra as $ie) {
                try {
                    $mp = \App\Models\MateriaPrima::findOrFail($ie['materia_prima_id']);
                    $todosIngredientes->push([
                        'materia_prima' => $mp,
                        'cantidad_necesaria' => (float) $ie['cantidad'],
                        'es_harina' => str_contains(strtolower($mp->nombre), 'harina'),
                    ]);
                } catch (\Exception $e) {
                    // Si no existe la materia prima pasada, tratamos como faltante
                    $ingredientes_faltantes[] = [
                        'ingrediente' => 'ID ' . ($ie['materia_prima_id'] ?? 'unknown'),
                        'necesario' => $ie['cantidad'] ?? 0,
                        'disponible' => 0,
                        'faltante' => $ie['cantidad'] ?? 0,
                    ];
                }
            }

            // Debug dump: ingredientes to process
            try {
                $dump = $todosIngredientes->map(function($it) {
                    return [
                        'id' => $it['materia_prima']->id ?? null,
                        'nombre' => $it['materia_prima']->nombre ?? null,
                        'stock_actual' => isset($it['materia_prima']->stock_actual) ? (float)$it['materia_prima']->stock_actual : null,
                        'cantidad_necesaria' => $it['cantidad_necesaria'],
                        'es_harina' => $it['es_harina'] ?? false,
                    ];
                })->toArray();
                file_put_contents('/tmp/produccion_debug_pre.json', json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) { /* ignore */ }

            // Agrupar requerimientos por materia_prima para verificar disponibilidad total
            $requerimientosPorMp = [];
            foreach ($todosIngredientes as $item) {
                $mpId = $item['materia_prima']->id ?? null;
                if (!$mpId) continue;

                $cantidad_necesaria = $item['cantidad_necesaria'];
                // Ajustar si es harina con valor manual
                if ($item['es_harina'] && $this->harina_real_usada) {
                    $cantidad_necesaria = $this->harina_real_usada;
                }

                if (!isset($requerimientosPorMp[$mpId])) {
                    $requerimientosPorMp[$mpId] = 0.0;
                }

                $requerimientosPorMp[$mpId] += (float)$cantidad_necesaria;
            }

            // Verificar disponibilidad con los totales agrupados
            foreach ($requerimientosPorMp as $mpId => $cantidadTotal) {
                $materia_prima = MateriaPrima::find($mpId);
                if (!$materia_prima) {
                    $ingredientes_faltantes[] = [
                        'ingrediente' => 'ID ' . $mpId,
                        'necesario' => $cantidadTotal,
                        'disponible' => 0,
                        'faltante' => $cantidadTotal,
                    ];
                    continue;
                }

                if ($materia_prima->stock_actual < $cantidadTotal) {
                    $ingredientes_faltantes[] = [
                        'ingrediente' => $materia_prima->nombre,
                        'necesario' => $cantidadTotal,
                        'disponible' => $materia_prima->stock_actual,
                        'faltante' => $cantidadTotal - $materia_prima->stock_actual,
                    ];
                }
            }

            // Si hay faltantes, abortar
            if (!empty($ingredientes_faltantes)) {
                try {
                    $pdo = DB::getPdo();
                    $inTx = $pdo ? $pdo->inTransaction() : false;
                } catch (\Exception $e) {
                    $inTx = false;
                }
                Log::info('Produccion::procesar - about to throw Stock insuficiente; pdoInTransaction=' . ($inTx ? '1' : '0'));
                throw new \Exception('Stock insuficiente: ' . json_encode($ingredientes_faltantes));
            }

            // DESCONTAR stock: procesar receta por separado y luego ingredientes extra para que queden movimientos distintos
            $costo_total = 0;

            // Si vino harina_real_usada, calcular harina_teorica/diferencia una sola vez
            if ($this->harina_real_usada) {
                $harinaIngred = $receta->ingredientes->first(fn($ing) => str_contains(strtolower($ing->materiaPrima->nombre), 'harina'));
                if ($harinaIngred) {
                    $this->harina_teorica = $harinaIngred->cantidad * $factor;
                    $this->diferencia_harina = $this->harina_real_usada - $this->harina_teorica;
                    if (abs($this->diferencia_harina) <= 0.05) {
                        $this->tipo_diferencia = 'normal';
                    } elseif ($this->diferencia_harina > 0) {
                        $this->tipo_diferencia = 'exceso';
                    } else {
                        $this->tipo_diferencia = 'merma';
                    }
                }
            }

            $producto_nombre = e($this->producto->nombre);

            // 1) Procesar ingredientes de la receta (cada uno por separado)
            foreach ($receta->ingredientes as $ing) {
                $mp = $ing->materiaPrima;
                $cantidadReq = $ing->cantidad * $factor;

                // Si es harina y viene harina_real_usada, usar ese valor en lugar del calculado
                if (str_contains(strtolower($mp->nombre), 'harina') && $this->harina_real_usada) {
                    $cantidadReq = $this->harina_real_usada;
                }

                Log::info('Produccion: descontando (receta)', [
                    'produccion_id' => $this->id,
                    'materia_prima_id' => $mp->id,
                    'nombre' => $mp->nombre,
                    'cantidad_necesaria' => $cantidadReq,
                    'stock_anterior' => $mp->stock_actual,
                ]);

                $mp->descontarStock(
                    $cantidadReq,
                    'salida_produccion',
                    $this->user_id,
                    "Producción #{$this->id} - {$this->cantidad_producida} {$producto_nombre}",
                    $this->id,
                    false
                );

                Log::info('Produccion: descontado (receta)', [
                    'produccion_id' => $this->id,
                    'materia_prima_id' => $mp->id,
                    'nombre' => $mp->nombre,
                    'cantidad_necesaria' => $cantidadReq,
                    'stock_nuevo' => $mp->fresh()->stock_actual,
                ]);

                try {
                    file_put_contents('/tmp/produccion_debug_post.json', json_encode([
                        'mp_id' => $mp->id,
                        'nombre' => $mp->nombre,
                        'cantidad' => $cantidadReq,
                        'stock_nuevo' => $mp->fresh()->stock_actual,
                    ]) . PHP_EOL, FILE_APPEND);
                } catch (\Exception $e) { /* ignore */ }

                $costo_total += $cantidadReq * $mp->costo_unitario;
            }

            // 2) Procesar ingredientes extras (cada uno por separado, como movimientos individuales)
            foreach ($ingredientesExtra as $ie) {
                $mp = \App\Models\MateriaPrima::findOrFail($ie['materia_prima_id']);
                $cantidadExtra = (float)$ie['cantidad'];

                Log::info('Produccion: descontando (extra)', [
                    'produccion_id' => $this->id,
                    'materia_prima_id' => $mp->id,
                    'nombre' => $mp->nombre,
                    'cantidad_extra' => $cantidadExtra,
                    'stock_anterior' => $mp->stock_actual,
                ]);

                $mp->descontarStock(
                    $cantidadExtra,
                    'salida_produccion',
                    $this->user_id,
                    "Producción #{$this->id} - Ingrediente extra",
                    $this->id,
                    false
                );

                Log::info('Produccion: descontado (extra)', [
                    'produccion_id' => $this->id,
                    'materia_prima_id' => $mp->id,
                    'nombre' => $mp->nombre,
                    'cantidad_extra' => $cantidadExtra,
                    'stock_nuevo' => $mp->fresh()->stock_actual,
                ]);

                try {
                    file_put_contents('/tmp/produccion_debug_post.json', json_encode([
                        'mp_id' => $mp->id,
                        'nombre' => $mp->nombre,
                        'cantidad' => $cantidadExtra,
                        'stock_nuevo' => $mp->fresh()->stock_actual,
                    ]) . PHP_EOL, FILE_APPEND);
                } catch (\Exception $e) { /* ignore */ }

                $costo_total += $cantidadExtra * $mp->costo_unitario;
            }

            // 3. Calcular costos
            $this->costo_produccion = round($costo_total, 2);
            $this->costo_unitario = round($costo_total / $this->cantidad_producida, 2);

            // 4. Agregar al inventario de producto final
            // Use query builder updateOrInsert to avoid nested transactions/savepoints
            // inside a larger transaction. firstOrCreate may start a nested
            // transaction; switching to updateOrInsert performs a single SQL
            // statement and avoids manipulating Laravel's transaction stack.
            InventarioProductoFinal::query()->updateOrInsert(
                ['producto_id' => $this->producto_id],
                [
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => 0,
                ]
            );

            // Reload the inventory row (should exist after updateOrInsert)
            $inventario = InventarioProductoFinal::where('producto_id', $this->producto_id)->first();

            $stock_anterior = $inventario->stock_actual;

            // Calcular costo promedio ponderado
            $costo_total_anterior = $stock_anterior * $inventario->costo_promedio;
            $costo_total_nuevo = $this->cantidad_producida * $this->costo_unitario;
            $stock_nuevo = $stock_anterior + $this->cantidad_producida;
            
            $inventario->stock_actual = $stock_nuevo;
            $inventario->costo_promedio = $stock_nuevo > 0 
                ? round(($costo_total_anterior + $costo_total_nuevo) / $stock_nuevo, 2)
                : 0;
            $inventario->fecha_elaboracion = $this->fecha_produccion;
            $inventario->save();

            // Registrar movimiento de producto final
            MovimientoProductoFinal::create([
                'producto_id' => $this->producto_id,
                'tipo_movimiento' => 'entrada_produccion',
                'cantidad' => $this->cantidad_producida,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'produccion_id' => $this->id,
                'user_id' => $this->user_id,
                'observaciones' => "Producción completada",
            ]);

            // 5. Marcar como completado
            $this->estado = 'completado';
            $this->save();

            // Invalidate dashboard cache now that production completed
            try { Cache::forget('inventario.dashboard'); } catch (\Exception $e) { /* silent */ }

            return $this;
        };

        // Detect if there's already an active PDO transaction and only start a new
        // one when there isn't. This avoids nested transaction/savepoint mismatches
        // when callers also manage transactions (or when tests wrap execution).
        // Always run the executor inside a DB::transaction so Laravel's
        // transaction manager handles nesting and savepoints consistently.
        // Add instrumentation logs to help debug savepoint mismatches.
        try {
            Log::info('Produccion::procesar - about to start DB::transaction', ['produccion_id' => $this->id, 'cantidad_producida' => $this->cantidad_producida]);
        } catch (\Throwable $e) { /* ignore logging errors */ }

        $result = SafeTransaction::run(function () use ($executor) {
            try { Log::info('Produccion::procesar - inside transaction (safe) start'); } catch (\Throwable $e) {}
            $res = $executor();
            try { Log::info('Produccion::procesar - inside transaction (safe) end'); } catch (\Throwable $e) {}
            return $res;
        });

        try {
            Log::info('Produccion::procesar - DB::transaction completed', ['produccion_id' => $this->id]);
        } catch (\Throwable $e) { /* ignore */ }

        return $result;
    }
}
