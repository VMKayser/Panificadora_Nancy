<?php

namespace App\Models;
use App\Support\HoraNegocio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    //
    use HasFactory, SoftDeletes;
    protected $table = 'productos';

    // Append computed attributes when serializing models to arrays/JSON
    protected $appends = ['stock'];

    protected $fillable = [
        'categorias_id',
        'nombre',
        'url',
        'descripcion',
        'descripcion_corta',
        'unidad_medida',
        'presentacion',
        'tiene_variantes',
        'tiene_extras',
        'extras_disponibles',
        'precio_minorista',
        'precio_mayorista',
        'precio_por_confirmar',
        'cantidad_minima_mayoreo',
        'es_de_temporada',
        'esta_activo',
        'permite_delivery',
        'permite_envio_nacional',
        'requiere_tiempo_anticipacion',
        'tiempo_anticipacion',
        'unidad_tiempo',
        'pedidos_hasta',
        'etiqueta_personalizacion',
        'limite_produccion',
    ];

    protected $casts = [
        'precio_minorista' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'precio_por_confirmar' => 'boolean',
        'pedidos_hasta' => 'date:Y-m-d',
        'es_de_temporada' => 'boolean',
        'esta_activo' => 'boolean',
        'permite_delivery' => 'boolean',
        'permite_envio_nacional' => 'boolean',
        'requiere_tiempo_anticipacion' => 'boolean',
        'tiene_extras' => 'boolean',
        'limite_produccion' => 'integer',
        'tiempo_anticipacion' => 'integer',
        'tiene_variantes' => 'boolean',
        'extras_disponibles' => 'array',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categorias_id' );
    }
    
    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id');
    }

    public function capacidadProduccion()
    {
        return $this->hasMany(CapacidadProduccion::class, 'producto_id');
    }

    public function inventario()
    {
        return $this->hasOne(InventarioProductoFinal::class, 'producto_id');
    }

    /**
     * Accesor conveniente para obtener el stock actual desde la tabla de inventario.
     * Uso: $producto->stock
     */
    public function getStockAttribute()
    {
        return $this->inventario?->stock_actual ?? 0;
    }

    /** La web ya no acepta pedidos: pasó el último día (hora de Bolivia) de pedidos_hasta. */
    public function pedidosCerrados(): bool
    {
        return $this->pedidos_hasta !== null
            && $this->pedidos_hasta->toDateString() < HoraNegocio::hoy();
    }

}