<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Support\SafeTransaction;

class MateriaPrima extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'materias_primas';

    protected $fillable = [
        'nombre',
        'codigo_interno',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'costo_unitario',
        'proveedor',
        'ultima_compra',
        'activo',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
        'ultima_compra' => 'date',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function movimientos()
    {
        return $this->hasMany(MovimientoMateriaPrima::class);
    }

    public function ingredientesReceta()
    {
        return $this->hasMany(IngredienteReceta::class);
    }

    // Métodos útiles
    public function estaEnStockMinimo()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function tieneStock($cantidad)
    {
        return $this->stock_actual >= $cantidad;
    }

    public function descontarStock($cantidad, $tipo_movimiento, $user_id, $observaciones = null, $produccion_id = null, $useTransaction = true)
    {
        // Validaciones
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad a descontar debe ser mayor a 0');
        }

        if ($this->stock_actual < $cantidad) {
            throw new \Exception("Stock insuficiente de {$this->nombre}. Disponible: {$this->stock_actual}, Requerido: {$cantidad}");
        }

    $callback = function () use ($cantidad, $tipo_movimiento, $user_id, $observaciones, $produccion_id) {
            $stock_anterior = $this->stock_actual;

            // Usar lockForUpdate para evitar race conditions
            $this->lockForUpdate()->decrement('stock_actual', $cantidad);
            $this->refresh();

            // Registrar movimiento
            MovimientoMateriaPrima::create([
                'materia_prima_id' => $this->id,
                'tipo_movimiento' => $tipo_movimiento,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $this->stock_actual,
                'produccion_id' => $produccion_id,
                'user_id' => $user_id,
                'observaciones' => $observaciones,
            ]);

            return $this;
        };

        if ($useTransaction) {
            try { Log::info('MateriaPrima::descontarStock - delegating to SafeTransaction', ['mp_id' => $this->id, 'cantidad' => $cantidad, 'useTransaction' => $useTransaction]); } catch (\Throwable $e) {}
            return SafeTransaction::run(function () use ($callback) {
                try { Log::info('MateriaPrima::descontarStock - inside safe transaction wrapper', ['mp_id' => $this->id]); } catch (\Throwable $e) {}
                return $callback();
            });
        }

        // Si ya estamos dentro de una transacción superior, ejecutar sin abrir una nueva
        return $callback();
    }

    // Reordered signature so optional parameters come last and provide safe defaults.
    // Accepts: cantidad, costo_unitario (optional), tipo_movimiento (defaults to 'entrada_compra'),
    // user_id (defaults to current Auth user if available), numero_factura (optional), observaciones (optional)
    public function agregarStock($cantidad, $costo_unitario = null, $tipo_movimiento = 'entrada_compra', $user_id = null, $numero_factura = null, $observaciones = null)
    {
        // Validaciones
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad a agregar debe ser mayor a 0');
        }

        if ($costo_unitario !== null && $costo_unitario < 0) {
            throw new \InvalidArgumentException('El costo unitario no puede ser negativo');
        }

        try { Log::info('MateriaPrima::agregarStock - delegating to SafeTransaction', ['mp_id' => $this->id, 'cantidad' => $cantidad]); } catch (\Throwable $e) {}
        return SafeTransaction::run(function () use ($cantidad, $costo_unitario, $tipo_movimiento, $user_id, $numero_factura, $observaciones) {
            try { Log::info('MateriaPrima::agregarStock - inside safe transaction wrapper', ['mp_id' => $this->id]); } catch (\Throwable $e) {}
            $stock_anterior = $this->stock_actual;
            // Resolve user id: if not provided, use currently authenticated user when possible
            $resolvedUserId = $user_id ?? Auth::id();
            
            // Usar lockForUpdate para evitar race conditions
            $this->lockForUpdate()->increment('stock_actual', $cantidad);
            $this->refresh();
            
            // Actualizar costo promedio ponderado si viene costo
            if ($costo_unitario !== null && $tipo_movimiento === 'entrada_compra') {
                $costo_total_anterior = $stock_anterior * $this->costo_unitario;
                $costo_total_nuevo = $cantidad * $costo_unitario;
                $stock_nuevo = $stock_anterior + $cantidad;
                
                // Protección contra división por cero
                $this->costo_unitario = $stock_nuevo > 0 
                    ? round(($costo_total_anterior + $costo_total_nuevo) / $stock_nuevo, 2)
                    : $costo_unitario;
                $this->save();
            }

            // Registrar movimiento
            MovimientoMateriaPrima::create([
                'materia_prima_id' => $this->id,
                'tipo_movimiento' => $tipo_movimiento,
                'cantidad' => $cantidad,
                'costo_unitario' => $costo_unitario ?? $this->costo_unitario,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $this->stock_actual,
                'user_id' => $resolvedUserId,
                'numero_factura' => $numero_factura,
                'observaciones' => $observaciones,
            ]);

            return $this;
            try { Log::info('MateriaPrima::agregarStock - inside transaction end', ['mp_id' => $this->id]); } catch (\Throwable $e) {}
        });
    }

    public function getValorInventarioAttribute()
    {
        return $this->stock_actual * $this->costo_unitario;
    }
}
