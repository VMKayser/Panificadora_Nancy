<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoProductoFinal extends Model
{
    protected $table = 'movimientos_productos_finales';

    protected $fillable = [
        'producto_id',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'produccion_id',
        'pedido_id',
        'user_id',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'stock_anterior' => 'decimal:3',
        'stock_nuevo' => 'decimal:3',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function produccion()
    {
        return $this->belongsTo(Produccion::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
