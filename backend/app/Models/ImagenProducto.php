<?php

namespace App\Models;

use App\Support\AssetUrl;
use App\Support\Miniaturas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ImagenProducto extends Model
{
    //
    use HasFactory;

    protected $table = 'imagen_producto';

    protected $fillable = [
        'producto_id',
        'url_imagen',
        'texto_alternativo',
        'es_imagen_principal',
        'order',
    ];

    protected $casts = [
        'es_imagen_principal' => 'boolean',
    ];
    
    protected $appends = ['url_imagen_completa', 'url_miniatura', 'url_mediana'];
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    
    /**
     * Accessor para obtener siempre la URL completa de la imagen
     * Esto asegura que la URL sea accesible desde el frontend
     */
    protected function urlImagenCompleta(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->url_imagen)) {
                    return null;
                }

                $url = $this->url_imagen;

                // Si no es absoluta, asegurar el prefijo /storage
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    if (!str_starts_with($url, '/')) {
                        $url = '/storage/' . ltrim($url, '/');
                    }
                }

                return AssetUrl::normalize($url);
            }
        );
    }

    /** Versión de 480 px para tarjetas y listas; el original si todavía no existe. */
    protected function urlMiniatura(): Attribute
    {
        return Attribute::make(
            get: fn () => Miniaturas::url($this->url_imagen_completa, Miniaturas::MINIATURA) ?? $this->url_imagen_completa
        );
    }

    /** Versión de 960 px para la ficha del producto; el original si todavía no existe. */
    protected function urlMediana(): Attribute
    {
        return Attribute::make(
            get: fn () => Miniaturas::url($this->url_imagen_completa, Miniaturas::MEDIANA) ?? $this->url_imagen_completa
        );
    }
}
