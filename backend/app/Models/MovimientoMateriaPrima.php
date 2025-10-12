<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoMateriaPrima extends Model
{
    protected $table = 'movimientos_materia_prima';

    protected $fillable = [
        'materia_prima_id',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'stock_anterior',
        'stock_nuevo',
        'produccion_id',
        'user_id',
        'observaciones',
        'numero_factura',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
        'stock_anterior' => 'decimal:3',
        'stock_nuevo' => 'decimal:3',
    ];

    public function materiaPrima()
    {
        return $this->belongsTo(MateriaPrima::class);
    }

    public function produccion()
    {
        return $this->belongsTo(Produccion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
