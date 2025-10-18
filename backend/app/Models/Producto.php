<?php

namespace App\Models;
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
        'cantidad_minima_mayoreo',
        'es_de_temporada',
        'esta_activo',
        'permite_delivery',
        'permite_envio_nacional',
        'requiere_tiempo_anticipacion',
        'tiempo_anticipacion',
        'unidad_tiempo',
        'limite_produccion',
    ];

    protected $casts = [
        'precio_minorista' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
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

}