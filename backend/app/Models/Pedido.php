<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos';
    
    protected $fillable = [
        'numero_pedido',
        'vendedor_id',
        'user_id',
        'cliente_id',
        'cliente_nombre',
        'cliente_apellido',
        'cliente_email',
        'cliente_telefono',
        'tipo_entrega',
        'direccion_entrega',
        'indicaciones_especiales',
        'notas_admin',
        'notas_cancelacion',
        'subtotal',
        'descuento',
        'descuento_bs',
        'motivo_descuento',
        'total',
        'metodos_pago_id',
        'codigo_promocional',
        'estado',
        'estado_pago',
        'qr_pago',
        'referencia_pago',
        'fecha_entrega',
        'hora_entrega',
        'fecha_pago',
        'envio_por_pagar',
        'empresa_transporte',
        'stock_descargado',
        'nit_ci_factura',
    ];

    /**
     * Flujo normal de un pedido. Solo se avanza (se pueden saltar pasos);
     * 'cancelado' es aparte y se permite desde cualquier estado no final.
     */
    public const FLUJO_ESTADOS = ['pendiente', 'confirmado', 'en_preparacion', 'listo', 'entregado'];
    public const ESTADOS_FINALES = ['entregado', 'cancelado'];
    public const ESTADOS_PAGO = ['pendiente', 'pagado', 'rechazado'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        // fecha_entrega guarda la hora local de Bolivia tal cual la eligió el cliente.
        // Se serializa sin zona ("2025-12-20T15:00:00") para que el navegador no la
        // convierta desde UTC y muestre el día anterior.
        'fecha_entrega' => 'datetime:Y-m-d\TH:i:s',
        'fecha_pago' => 'datetime',
        'stock_descargado' => 'boolean',
    ];

    public function puedeCambiarA(string $nuevoEstado): bool
    {
        if (in_array($this->estado, self::ESTADOS_FINALES, true)) {
            return false;
        }
        if ($nuevoEstado === 'cancelado') {
            return true;
        }

        $actual = array_search($this->estado, self::FLUJO_ESTADOS, true);
        $nuevo = array_search($nuevoEstado, self::FLUJO_ESTADOS, true);

        return $actual !== false && $nuevo !== false && $nuevo > $actual;
    }

    /**
     * Siguiente número con el prefijo dado (ej. "PED-2026-" → "PED-2026-0015").
     * Debe llamarse dentro de una transacción: el lockForUpdate sobre el índice
     * único serializa a dos pedidos simultáneos.
     */
    public static function generarNumero(string $prefijo): string
    {
        $ultimo = static::withTrashed()
            ->where('numero_pedido', 'like', $prefijo . '%')
            ->orderByRaw('LENGTH(numero_pedido) DESC')
            ->orderBy('numero_pedido', 'desc')
            ->lockForUpdate()
            ->value('numero_pedido');

        $siguiente = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodos_pago_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedidos_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    protected static function booted()
    {
        static::created(function ($pedido) {
            if ($pedido->cliente_id) {
                $pedido->cliente->actualizarEstadisticas();
            }
        });

        static::updated(function ($pedido) {
            if ($pedido->cliente_id) {
                $pedido->cliente->actualizarEstadisticas();
            }
        });
    }
}
