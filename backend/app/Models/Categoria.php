<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    //
    use HasFactory;

    protected $table = 'categorias';
    
    protected $fillable = [
        'nombre', 
        'url', 
        'descripcion',
        'imagen',
        'esta_activo',
        'orden',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categorias_id');
    }

}
