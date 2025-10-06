<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos';
    
    protected $fillable = [
        'numero_pedido',
        'user_id',
        'cliente_nombre',
        'cliente_apellido',
        'cliente_email',
        'cliente_telefono',
        'tipo_entrega',
        'direccion_entrega',
        'indicaciones_especiales',
        'subtotal',
        'descuento',
        'total',
        'metodos_pago_id',
        'codigo_promocional',
        'estado',
        'estado_pago',
        'qr_pago',
        'referencia_pago',
        'fecha_entrega',
        'fecha_pago',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_entrega' => 'datetime',
        'fecha_pago' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodos_pago_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedidos_id');
    }
}
