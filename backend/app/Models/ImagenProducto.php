<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
