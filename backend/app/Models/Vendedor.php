<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendedores';

    protected $fillable = [
        'user_id',
        'codigo_vendedor',
        'comision_porcentaje',
        'comision_acumulada',
        'descuento_maximo_bs',
        'puede_dar_descuentos',
        'puede_cancelar_ventas',
        'turno',
        'fecha_ingreso',
        'estado',
        'observaciones',
        'ventas_realizadas',
        'total_vendido',
        'descuentos_otorgados',
    ];

    protected $casts = [
        'comision_porcentaje' => 'decimal:2',
        'comision_acumulada' => 'decimal:2',
        'descuento_maximo_bs' => 'decimal:2',
        'puede_dar_descuentos' => 'boolean',
        'puede_cancelar_ventas' => 'boolean',
        'fecha_ingreso' => 'date',
        'ventas_realizadas' => 'integer',
        'total_vendido' => 'decimal:2',
        'descuentos_otorgados' => 'decimal:2',
    ];

    protected $appends = ['nombre_completo'];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'vendedor_id');
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return $this->user ? $this->user->name : 'Sin usuario';
    }

    // Métodos de negocio
    public function puedeOtorgarDescuento($montoDescuento)
    {
        if (!$this->puede_dar_descuentos) {
            return false;
        }

        return $montoDescuento <= $this->descuento_maximo_bs;
    }

    public function registrarVenta($total, $descuento = 0)
    {
        $this->increment('ventas_realizadas');
        $this->increment('total_vendido', $total);
        
        if ($descuento > 0) {
            $this->increment('descuentos_otorgados', $descuento);
        }
    }

    public function calcularComision($totalVentas = null)
    {
        $ventas = $totalVentas ?? $this->total_vendido;
        return ($ventas * $this->comision_porcentaje) / 100;
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTurno($query, $turno)
    {
        return $query->where('turno', $turno);
    }

    // Generar código único de vendedor
    public static function generarCodigoVendedor()
    {
        $ultimoVendedor = self::orderBy('id', 'desc')->first();
        $numero = $ultimoVendedor ? ($ultimoVendedor->id + 1) : 1;
        return 'VEN-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
