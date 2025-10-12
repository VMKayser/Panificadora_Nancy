<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioProductoFinal extends Model
{
    protected $table = 'inventario_productos_finales';

    protected $fillable = [
        'producto_id',
        'stock_actual',
        'stock_minimo',
        'fecha_elaboracion',
        'dias_vida_util',
        'fecha_vencimiento',
        'costo_promedio',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'fecha_elaboracion' => 'date',
        'fecha_vencimiento' => 'date',
        'costo_promedio' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoProductoFinal::class, 'producto_id', 'producto_id');
    }

    public function getValorInventarioAttribute()
    {
        return $this->stock_actual * $this->costo_promedio;
    }

    public function estaEnStockMinimo()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function estaVencido()
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento < now();
    }

    public function diasParaVencer()
    {
        if (!$this->fecha_vencimiento) return null;
        return now()->diffInDays($this->fecha_vencimiento, false);
    }
}
