<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';
    
    protected $fillable = [
        'pedidos_id',
        'productos_id',
        'nombre_producto',
        'precio_unitario',
        'cantidad',
        'subtotal',
        'requiere_anticipacion',
        'tiempo_anticipacion',
        'unidad_tiempo',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'requiere_anticipacion' => 'boolean',
    ];


    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedidos_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'productos_id');
    }
}
