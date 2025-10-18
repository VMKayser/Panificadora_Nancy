<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpleadoPago extends Model
{
    use HasFactory;

    protected $table = 'empleado_pagos';

    protected $fillable = [
        'empleado_tipo',
        'empleado_id',
        'monto',
        'kilos_pagados',
        'metodos_pago_id',
        'notas',
        'created_by',
        'tipo_pago',
        'es_sueldo_fijo',
        'comision_pagada',
    ];

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodos_pago_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'empleado_id');
    }

    public function panadero()
    {
        return $this->belongsTo(Panadero::class, 'empleado_id');
    }

    // Polymorphic relation to either Panadero or Vendedor
    public function empleadoable()
    {
        return $this->morphTo();
    }
}
