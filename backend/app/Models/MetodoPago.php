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

    // Accessor para obtener URL completa del icono (si existe)
    public function getIconoUrlAttribute()
    {
        if (!$this->icono) return null;
        // Si ya es una URL absoluta
        if (preg_match('/^https?:\/\//', $this->icono)) {
            return $this->icono;
        }
        // Normalmente los iconos se guardan en storage/app/public -> usar asset()
        return url($this->icono);
    }


    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'metodos_pago_id');
    }
}
