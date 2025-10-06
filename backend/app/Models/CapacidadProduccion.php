<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapacidadProduccion extends Model
{
    //
    use HasFactory;

    protected $table = 'capacidad_produccion';

    protected $fillable = [
        'producto_id',
        'limite_semanal',
        'semana_inicio',
        'semana_fin',
        'cantidad_reservada',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
    
    ];
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }


    public function getCantidadDisponibleAttribute()
    {
        return $this->limite_semanal - $this->cantidad_reservada;
    }
}
