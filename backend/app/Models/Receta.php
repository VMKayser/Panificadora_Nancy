<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Receta extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'producto_id',
        'nombre_receta',
        'descripcion',
        'rendimiento',
        'unidad_rendimiento',
        'costo_total_calculado',
        'costo_unitario_calculado',
        'activa',
        'version',
    ];

    protected $casts = [
        'rendimiento' => 'decimal:3',
        'costo_total_calculado' => 'decimal:2',
        'costo_unitario_calculado' => 'decimal:2',
        'activa' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ingredientes()
    {
        return $this->hasMany(IngredienteReceta::class);
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }

    /**
     * Calcular costo total de la receta basado en ingredientes
     */
    public function calcularCostos()
    {
        // Validación
        if ($this->rendimiento <= 0) {
            throw new \Exception('El rendimiento de la receta debe ser mayor a 0');
        }

        // Cargar ingredientes con materia prima (evitar N+1)
        $this->load('ingredientes.materiaPrima');

        return DB::transaction(function () {
            $costo_total = 0;

            foreach ($this->ingredientes as $ingrediente) {
                $materia_prima = $ingrediente->materiaPrima;
                
                if (!$materia_prima) {
                    throw new \Exception("Materia prima no encontrada para ingrediente ID: {$ingrediente->id}");
                }
                
                // Convertir cantidad a unidad base si es necesario
                $cantidad_base = $this->convertirAUnidadBase(
                    $ingrediente->cantidad,
                    $ingrediente->unidad,
                    $materia_prima->unidad_medida
                );

                $costo_ingrediente = round($cantidad_base * $materia_prima->costo_unitario, 2);
                $costo_total += $costo_ingrediente;

                // Actualizar costo del ingrediente
                $ingrediente->update(['costo_calculado' => $costo_ingrediente]);
            }

            $costo_unitario = round($costo_total / $this->rendimiento, 2);

            $this->update([
                'costo_total_calculado' => round($costo_total, 2),
                'costo_unitario_calculado' => $costo_unitario,
            ]);

            return $this;
        });
    }

    /**
     * Convertir unidades (ej: 500g a 0.5kg)
     */
    private function convertirAUnidadBase($cantidad, $unidad_origen, $unidad_destino)
    {
        // Si son iguales, no convertir
        if ($unidad_origen === $unidad_destino) {
            return $cantidad;
        }

        // Conversiones de peso
        if ($unidad_origen === 'g' && $unidad_destino === 'kg') {
            return $cantidad / 1000;
        }
        if ($unidad_origen === 'kg' && $unidad_destino === 'g') {
            return $cantidad * 1000;
        }

        // Conversiones de volumen
        if ($unidad_origen === 'ml' && $unidad_destino === 'L') {
            return $cantidad / 1000;
        }
        if ($unidad_origen === 'L' && $unidad_destino === 'ml') {
            return $cantidad * 1000;
        }

        // Si no hay conversión disponible, lanzar excepción
        throw new \Exception("No se puede convertir de {$unidad_origen} a {$unidad_destino}");
    }

    /**
     * Verificar si hay suficientes ingredientes en stock
     */
    public function verificarStock($cantidad_producir)
    {
        // Validaciones
        if ($cantidad_producir <= 0) {
            throw new \InvalidArgumentException('La cantidad a producir debe ser mayor a 0');
        }

        if ($this->rendimiento <= 0) {
            throw new \Exception('El rendimiento de la receta debe ser mayor a 0');
        }

        // Cargar ingredientes con materia prima (evitar N+1)
        $this->load('ingredientes.materiaPrima');

        $faltantes = [];

        foreach ($this->ingredientes as $ingrediente) {
            $materia_prima = $ingrediente->materiaPrima;

            if (!$materia_prima) {
                throw new \Exception("Materia prima no encontrada para ingrediente ID: {$ingrediente->id}");
            }

            $cantidad_necesaria = round(($ingrediente->cantidad / $this->rendimiento) * $cantidad_producir, 3);

            if (!$materia_prima->tieneStock($cantidad_necesaria)) {
                $faltantes[] = [
                    'ingrediente' => $materia_prima->nombre,
                    'codigo' => $materia_prima->codigo_interno,
                    'necesario' => $cantidad_necesaria,
                    'disponible' => $materia_prima->stock_actual,
                    'faltante' => round($cantidad_necesaria - $materia_prima->stock_actual, 3),
                    'unidad' => $materia_prima->unidad_medida,
                ];
            }
        }

        return [
            'tiene_stock' => empty($faltantes),
            'faltantes' => $faltantes,
        ];
    }
}
