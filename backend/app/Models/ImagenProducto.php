<?php

namespace App\Models;
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
    
    protected $appends = ['url_imagen_completa'];
    
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
                
                // Si ya tiene el protocolo, devolverla tal cual
                if (str_starts_with($this->url_imagen, 'http://') || str_starts_with($this->url_imagen, 'https://')) {
                    return $this->url_imagen;
                }
                
                // Si comienza con /storage/, agregar la URL base
                if (str_starts_with($this->url_imagen, '/storage/')) {
                    return config('app.url') . $this->url_imagen;
                }
                
                // Si es solo el path del archivo (productos/archivo.jpg)
                if (!str_starts_with($this->url_imagen, '/')) {
                    return config('app.url') . '/storage/' . $this->url_imagen;
                }
                
                // Por defecto, agregar la URL base
                return config('app.url') . $this->url_imagen;
            }
        );
    }
}
