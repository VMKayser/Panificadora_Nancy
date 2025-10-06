<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';
    
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'icono',
        'esta_activo',
        'comision_porcentaje',
        'orden',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'comision_porcentaje' => 'decimal:2',
    ];


    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'metodos_pago_id');
    }
}
