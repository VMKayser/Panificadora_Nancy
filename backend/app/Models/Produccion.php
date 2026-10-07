<?php

namespace App\Models;

use App\Exceptions\StockInsuficienteException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'panadero_id',
        'fecha_produccion',
        'hora_inicio',
        'hora_fin',
        'cantidad_producida',
        'cantidad_kg',
        'cantidad_unidades',
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

    public function panadero()
    {
        return $this->belongsTo(Panadero::class, 'panadero_id');
    }

    public function movimientosMateriaPrima()
    {
        return $this->hasMany(MovimientoMateriaPrima::class);
    }

    public function movimientosProductoFinal()
    {
        return $this->hasMany(MovimientoProductoFinal::class);
    }

    public static function esHarina(?MateriaPrima $mp): bool
    {
        return $mp !== null && str_contains(mb_strtolower($mp->nombre), 'harina');
    }

    /**
     * Procesar la producción: descontar ingredientes, actualizar stock producto final
     * Acepta un array opcional de ingredientes adicionales en formato:
     * [ ['materia_prima_id' => int, 'cantidad' => float, 'modo' => 'extra'|'override'], ... ]
     * - 'extra': se descuenta además de la receta.
     * - 'override': reemplaza la cantidad de ese ingrediente de la receta.
     * Las cantidades vienen en la unidad de stock de la materia prima.
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

        $ingredientesExtra = array_values(array_filter(array_map(function ($ing) {
            $mpId = isset($ing['materia_prima_id']) ? (int) $ing['materia_prima_id'] : 0;
            $cantidad = isset($ing['cantidad']) ? (float) $ing['cantidad'] : 0;

            if ($mpId <= 0 || $cantidad <= 0) {
                return null;
            }

            return [
                'materia_prima_id' => $mpId,
                'cantidad' => round($cantidad, 3),
                'modo' => $ing['modo'] ?? null,
            ];
        }, $ingredientesExtra)));

        $executor = function () use ($receta, $ingredientesExtra) {
            $factor = $receta->factorPara((float) $this->cantidad_producida, $this->unidad);

            $overrideIngredientes = [];
            $extras = [];
            foreach ($ingredientesExtra as $ie) {
                if (($ie['modo'] ?? null) === 'override') {
                    $overrideIngredientes[$ie['materia_prima_id']] = $ie['cantidad'];
                } else {
                    $extras[] = $ie;
                }
            }

            // 1) Necesidades de la receta, en la unidad de stock de cada materia prima
            $necesidades = $receta->ingredientes->map(function ($ing) use ($factor, $overrideIngredientes) {
                $mp = $ing->materiaPrima;
                if (!$mp) {
                    throw new \Exception("Materia prima no encontrada para ingrediente ID: {$ing->id}");
                }
                $cantidad = array_key_exists($mp->id, $overrideIngredientes)
                    ? (float) $overrideIngredientes[$mp->id]
                    : Receta::convertirUnidad($ing->cantidad, $ing->unidad, $mp->unidad_medida) * $factor;

                return [
                    'materia_prima_id' => $mp->id,
                    'cantidad' => $cantidad,
                    'es_harina' => self::esHarina($mp),
                    'origen' => 'receta',
                ];
            })->values();

            // Overrides de ingredientes que no están en la receta: se descuentan aparte
            $enReceta = $necesidades->pluck('materia_prima_id')->all();
            foreach ($overrideIngredientes as $mpId => $cantidad) {
                if (!in_array($mpId, $enReceta, true)) {
                    $extras[] = ['materia_prima_id' => $mpId, 'cantidad' => $cantidad];
                }
            }

            // 2) Harina: la teórica sale de la receta. Si se indicó la real, las
            // líneas de harina se escalan a ese total; si no, se usa la teórica
            // (y es la que cuenta para el pago del panadero).
            $harinaTeorica = (float) $necesidades->where('es_harina', true)->sum('cantidad');
            $harinaReal = (float) ($this->harina_real_usada ?? 0);
            if ($harinaTeorica > 0) {
                if ($harinaReal > 0) {
                    $escala = $harinaReal / $harinaTeorica;
                    $necesidades = $necesidades->map(fn ($n) => $n['es_harina'] ? ['cantidad' => $n['cantidad'] * $escala] + $n : $n);
                } else {
                    $harinaReal = $harinaTeorica;
                    $this->harina_real_usada = round($harinaTeorica, 3);
                }
                $diferencia = round($harinaReal - $harinaTeorica, 3);
                $this->harina_teorica = round($harinaTeorica, 3);
                $this->diferencia_harina = $diferencia;
                $this->tipo_diferencia = abs($diferencia) <= 0.05 ? 'normal' : ($diferencia > 0 ? 'exceso' : 'merma');
            }

            $consumos = $necesidades
                ->concat(array_map(fn ($e) => [
                    'materia_prima_id' => $e['materia_prima_id'],
                    'cantidad' => (float) $e['cantidad'],
                    'es_harina' => false,
                    'origen' => 'extra',
                ], $extras))
                ->map(fn ($c) => ['cantidad' => round($c['cantidad'], 3)] + $c)
                ->filter(fn ($c) => $c['cantidad'] > 0)
                ->values();

            // 3) Verificar stock de todo junto, con las filas bloqueadas
            $mps = MateriaPrima::whereIn('id', $consumos->pluck('materia_prima_id')->unique()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $faltantes = [];
            foreach ($consumos->groupBy('materia_prima_id') as $mpId => $lineas) {
                $necesario = round($lineas->sum('cantidad'), 3);
                $mp = $mps->get($mpId);
                $disponible = $mp ? (float) $mp->stock_actual : 0.0;
                if ($disponible < $necesario) {
                    $faltantes[] = [
                        'ingrediente' => $mp->nombre ?? "ID {$mpId}",
                        'necesario' => $necesario,
                        'disponible' => $disponible,
                        'faltante' => round($necesario - $disponible, 3),
                        'unidad' => $mp->unidad_medida ?? null,
                    ];
                }
            }
            if (!empty($faltantes)) {
                throw new StockInsuficienteException('Stock insuficiente de ingredientes', $faltantes);
            }

            // 4) Descontar: cada línea de receta y cada extra deja su propio movimiento
            $producto_nombre = e($this->producto->nombre);
            $costo_total = 0;
            foreach ($consumos as $consumo) {
                $mp = $mps->get($consumo['materia_prima_id']);
                $observacion = $consumo['origen'] === 'receta'
                    ? "Producción #{$this->id} - {$this->cantidad_producida} {$producto_nombre}"
                    : "Producción #{$this->id} - Ingrediente extra";

                $mp->descontarStock($consumo['cantidad'], 'salida_produccion', $this->user_id, $observacion, $this->id, false);
                $costo_total += $consumo['cantidad'] * $mp->costo_unitario;
            }

            // 5) Costos por unidad de stock (las docenas se guardan como unidades)
            $unidadesStock = Receta::aUnidadesStock((float) $this->cantidad_producida, $this->unidad);
            $this->costo_produccion = round($costo_total, 2);
            $this->costo_unitario = round($costo_total / $unidadesStock, 2);

            // 6) Sumar al inventario de producto final (crear la fila si falta, sin pisar el stock existente)
            InventarioProductoFinal::insertOrIgnore([
                'producto_id' => $this->producto_id,
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'costo_promedio' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inventario = InventarioProductoFinal::where('producto_id', $this->producto_id)->lockForUpdate()->first();

            $stock_anterior = (float) $inventario->stock_actual;
            $stock_nuevo = $stock_anterior + $unidadesStock;

            // Costo promedio ponderado
            $costo_total_anterior = $stock_anterior * (float) $inventario->costo_promedio;
            $inventario->stock_actual = $stock_nuevo;
            $inventario->costo_promedio = $stock_nuevo > 0
                ? round(($costo_total_anterior + $costo_total) / $stock_nuevo, 2)
                : 0;
            $inventario->fecha_elaboracion = $this->fecha_produccion;
            $inventario->save();

            MovimientoProductoFinal::create([
                'producto_id' => $this->producto_id,
                'tipo_movimiento' => 'entrada_produccion',
                'cantidad' => $unidadesStock,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'produccion_id' => $this->id,
                'user_id' => $this->user_id,
                'observaciones' => $this->unidad === 'docenas'
                    ? "Producción completada ({$this->cantidad_producida} docenas)"
                    : 'Producción completada',
            ]);

            // 7) Marcar como completado
            $this->estado = 'completado';
            $this->save();

            // Invalidate dashboard cache now that production completed
            try { Cache::forget('inventario.dashboard'); } catch (\Exception $e) { /* silent */ }

            return $this;
        };

        try {
            Log::info('Produccion::procesar - about to start DB::transaction', ['produccion_id' => $this->id, 'cantidad_producida' => $this->cantidad_producida]);
        } catch (\Throwable $e) { /* ignore logging errors */ }

        return SafeTransaction::run($executor);
    }

    /**
     * Cancela la producción devolviendo lo que movió: repone las materias primas
     * y retira del inventario las unidades que sumó. Si esas unidades ya se
     * vendieron no se puede revertir (habría que ajustar el inventario a mano).
     */
    public function revertir(string $motivo, ?int $userId = null): void
    {
        SafeTransaction::run(function () use ($motivo, $userId) {
            if ($this->estado === 'completado') {
                $entradas = (float) MovimientoProductoFinal::where('produccion_id', $this->id)
                    ->where('tipo_movimiento', 'entrada_produccion')
                    ->sum('cantidad');

                if ($entradas > 0) {
                    $inventario = InventarioProductoFinal::where('producto_id', $this->producto_id)->lockForUpdate()->first();
                    $stockAnterior = (float) ($inventario->stock_actual ?? 0);

                    if ($stockAnterior < $entradas) {
                        throw new StockInsuficienteException(
                            "No se puede cancelar: la producción sumó {$entradas} unidades y solo quedan {$stockAnterior} en stock (el resto ya salió). Ajusta el inventario manualmente.",
                            [['producto' => $this->producto->nombre ?? $this->producto_id, 'producido' => $entradas, 'disponible' => $stockAnterior]]
                        );
                    }

                    $stockNuevo = $stockAnterior - $entradas;
                    $inventario->update(['stock_actual' => $stockNuevo]);

                    MovimientoProductoFinal::create([
                        'producto_id' => $this->producto_id,
                        'tipo_movimiento' => 'ajuste',
                        'cantidad' => $entradas,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'produccion_id' => $this->id,
                        'user_id' => $userId,
                        'observaciones' => "Reversión por cancelación de producción #{$this->id}",
                    ]);
                }

                $consumos = MovimientoMateriaPrima::where('produccion_id', $this->id)
                    ->where('tipo_movimiento', 'salida_produccion')
                    ->get()
                    ->groupBy('materia_prima_id');

                foreach ($consumos as $mpId => $movs) {
                    $mp = MateriaPrima::withTrashed()->find($mpId);
                    $cantidad = (float) $movs->sum('cantidad');
                    if ($mp && $cantidad > 0) {
                        $mp->agregarStock($cantidad, null, 'entrada_devolucion', $userId, null, "Reversión por cancelación de producción #{$this->id}");
                    }
                }
            }

            $this->estado = 'cancelado';
            $this->observaciones = trim(($this->observaciones ?? '') . "\n\nCANCELADO: " . $motivo);
            $this->save();
        });

        try { Cache::forget('inventario.dashboard'); } catch (\Exception $e) { /* silent */ }
    }
}
