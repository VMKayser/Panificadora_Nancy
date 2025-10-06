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

    protected $fillable = [
        'categorias_id',
        'nombre',
        'url',
        'descripcion',
        'descripcion_corta',
        'precio_minorista',
        'precio_mayorista',
        'cantidad_minima_mayoreo',
        'es_de_temporada',
        'esta_activo',
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
        'requiere_tiempo_anticipacion' => 'boolean',
        'limite_produccion' => 'boolean',
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

}