<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'email',
        'telefono',
        'direccion',
        'ci',
        'nit_ci',
        'tipo_cliente',
        'total_pedidos',
        'total_gastado',
        'fecha_ultimo_pedido',
        'activo',
        'notas'
    ];

    protected $casts = [
        'total_gastado' => 'decimal:2',
        'fecha_ultimo_pedido' => 'date',
        'activo' => 'boolean',
    ];

    // Relación con usuario
    public function user()
    {
        // Prefer relation by user_id if present (new normalized schema).
        // Keep compatibility: if user_id is null, allow lookup by email.
        if ($this->user_id) {
            return $this->belongsTo(User::class);
        }

        // Fallback to email-based relation for legacy rows.
        return $this->belongsTo(User::class, 'email', 'email');
    }

    // Relación con pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    // Accessor para nombre completo
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }

    // Método para actualizar estadísticas
    public function actualizarEstadisticas()
    {
        $this->total_pedidos = $this->pedidos()->count();
        $this->total_gastado = $this->pedidos()
            ->whereNotIn('estado', ['cancelado'])
            ->sum('total');
        $this->fecha_ultimo_pedido = $this->pedidos()
            ->latest()
            ->first()?->created_at;
        $this->save();
    }
}
