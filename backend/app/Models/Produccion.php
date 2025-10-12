<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
     * Procesar producción: descontar ingredientes, actualizar stock producto final
     */
    public function procesar()
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
        return DB::transaction(function () use ($receta) {
            // 1. VERIFICAR STOCK SUFICIENTE PRIMERO
            $factor = $this->cantidad_producida / $receta->rendimiento;
            $ingredientes_faltantes = [];

            foreach ($receta->ingredientes as $ingrediente) {
                $materia_prima = $ingrediente->materiaPrima;
                $cantidad_necesaria = $ingrediente->cantidad * $factor;

                // Ajustar si es harina con valor manual
                if (str_contains(strtolower($materia_prima->nombre), 'harina') && $this->harina_real_usada) {
                    $cantidad_necesaria = $this->harina_real_usada;
                }

                // Verificar stock disponible
                if ($materia_prima->stock_actual < $cantidad_necesaria) {
                    $ingredientes_faltantes[] = [
                        'ingrediente' => $materia_prima->nombre,
                        'necesario' => $cantidad_necesaria,
                        'disponible' => $materia_prima->stock_actual,
                        'faltante' => $cantidad_necesaria - $materia_prima->stock_actual,
                    ];
                }
            }

            // Si hay faltantes, abortar
            if (!empty($ingredientes_faltantes)) {
                throw new \Exception('Stock insuficiente: ' . json_encode($ingredientes_faltantes));
            }

            // 2. DESCONTAR INGREDIENTES DE MATERIA PRIMA
            $costo_total = 0;

            // 2. DESCONTAR INGREDIENTES DE MATERIA PRIMA
            $costo_total = 0;

            foreach ($receta->ingredientes as $ingrediente) {
                $materia_prima = $ingrediente->materiaPrima;
                $cantidad_necesaria = $ingrediente->cantidad * $factor;

                // Si es harina y hay valor manual, usar ese
                if (str_contains(strtolower($materia_prima->nombre), 'harina') && $this->harina_real_usada) {
                    $cantidad_necesaria = $this->harina_real_usada;
                    $this->harina_teorica = $ingrediente->cantidad * $factor;
                    $this->diferencia_harina = $this->harina_real_usada - $this->harina_teorica;
                    
                    // Clasificar tipo de diferencia
                    if (abs($this->diferencia_harina) <= 0.05) { // ±50g es normal
                        $this->tipo_diferencia = 'normal';
                    } elseif ($this->diferencia_harina > 0) {
                        $this->tipo_diferencia = 'exceso';
                    } else {
                        $this->tipo_diferencia = 'merma';
                    }
                }

                // Descontar stock (sanitizar nombre producto)
                $producto_nombre = e($this->producto->nombre); // Escapar HTML
                $materia_prima->descontarStock(
                    $cantidad_necesaria,
                    'salida_produccion',
                    $this->user_id,
                    "Producción #{$this->id} - {$this->cantidad_producida} {$producto_nombre}",
                    $this->id
                );

                $costo_total += $cantidad_necesaria * $materia_prima->costo_unitario;
            }

            // 3. Calcular costos
            $this->costo_produccion = round($costo_total, 2);
            $this->costo_unitario = round($costo_total / $this->cantidad_producida, 2);

            // 4. Agregar al inventario de producto final
            $inventario = InventarioProductoFinal::firstOrCreate(
                ['producto_id' => $this->producto_id],
                [
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => 0,
                ]
            );

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

            return $this;
        }); // FIN TRANSACCIÓN
    }
}
