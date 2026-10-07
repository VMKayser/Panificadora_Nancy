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
        try {
            return self::convertirUnidad($cantidad, $unidad_origen, $unidad_destino);
        } catch (\InvalidArgumentException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Convierte una cantidad de ingrediente a la unidad en que se lleva el stock
     * de la materia prima (g<->kg, ml<->L). Sin unidad de origen se asume la
     * misma de la materia prima.
     */
    public static function convertirUnidad($cantidad, ?string $unidadOrigen, ?string $unidadDestino): float
    {
        $cantidad = (float) $cantidad;
        if (!$unidadOrigen || !$unidadDestino || $unidadOrigen === $unidadDestino) {
            return $cantidad;
        }

        $factores = [
            'g' => ['kg', 0.001],
            'kg' => ['g', 1000],
            'ml' => ['L', 0.001],
            'L' => ['ml', 1000],
        ];
        if (isset($factores[$unidadOrigen]) && $factores[$unidadOrigen][0] === $unidadDestino) {
            return $cantidad * $factores[$unidadOrigen][1];
        }

        throw new \InvalidArgumentException("No se puede convertir de {$unidadOrigen} a {$unidadDestino}");
    }

    /** Las docenas se llevan en el stock como unidades. */
    public static function aUnidadesStock(float $cantidad, ?string $unidad): float
    {
        return $unidad === 'docenas' ? $cantidad * 12 : $cantidad;
    }

    /**
     * Cuántas veces se hace la receta para producir $cantidad en $unidad
     * (unidades, docenas o kg). Unidades y docenas se convierten entre sí;
     * kg solo es compatible con recetas que rinden en kg.
     */
    public function factorPara(float $cantidad, ?string $unidad = null): float
    {
        $unidadReceta = $this->unidad_rendimiento ?: 'unidades';
        $unidad = $unidad ?: $unidadReceta;

        if (($unidad === 'kg') !== ($unidadReceta === 'kg')) {
            throw new \InvalidArgumentException(
                "La receta rinde en {$unidadReceta} y la producción se registró en {$unidad}. Registra la producción en la misma unidad que la receta."
            );
        }

        return self::aUnidadesStock($cantidad, $unidad) / self::aUnidadesStock((float) $this->rendimiento, $unidadReceta);
    }

    /**
     * Verificar si hay suficientes ingredientes en stock
     */
    public function verificarStock($cantidad_producir, ?string $unidad = null)
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

        $factor = $this->factorPara((float) $cantidad_producir, $unidad);
        $faltantes = [];

        foreach ($this->ingredientes as $ingrediente) {
            $materia_prima = $ingrediente->materiaPrima;

            if (!$materia_prima) {
                throw new \Exception("Materia prima no encontrada para ingrediente ID: {$ingrediente->id}");
            }

            $cantidad_necesaria = round(
                self::convertirUnidad($ingrediente->cantidad, $ingrediente->unidad, $materia_prima->unidad_medida) * $factor,
                3
            );

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
