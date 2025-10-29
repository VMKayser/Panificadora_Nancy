<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Panadero extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'panaderos';

    protected $fillable = [
        'user_id',
        'codigo_panadero',
        // contact fields are stored in users table for normalization
        'direccion',
        'fecha_ingreso',
        'turno',
        'especialidad',
        'salario_base',
        'salario_por_kilo',
        'total_kilos_producidos',
        'total_unidades_producidas',
        'ultima_produccion',
        'activo',
        'observaciones'
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'ultima_produccion' => 'date',
        'salario_base' => 'decimal:2',
        'salario_por_kilo' => 'decimal:2',
        'activo' => 'boolean',
    ];

    protected $appends = ['salario_por_kilo_total'];

    public function getSalarioPorKiloTotalAttribute()
    {
        $precio = floatval($this->salario_por_kilo ?? 0);
        $kg = intval($this->total_kilos_producidos ?? 0);
        return number_format($precio * $kg, 2, '.', '');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }

    public function getNombreCompletoAttribute()
    {
        // Si hay user_id, usar nombre del usuario
        if ($this->user) {
            return $this->user->name;
        }
        // Si no hay user vinculado, intentar fallback legacy
        return trim((($this->nombre ?? '') . ' ' . ($this->apellido ?? '')));
    }
    
    // Generar código único de panadero
    public static function generarCodigoPanadero()
    {
        $ultimoPanadero = self::orderBy('id', 'desc')->first();
        $numero = $ultimoPanadero ? ($ultimoPanadero->id + 1) : 1;
        return 'PAN-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function actualizarEstadisticas()
    {
        // Calcular kilos pagables por producción: max(0, cantidad_kg - harina_real_usada)
        $producciones = $this->producciones()->get();

        $total_kilos = 0.0;
        foreach ($producciones as $p) {
            $cantidad_kg = floatval($p->cantidad_kg ?? 0);
            $harina_usada = floatval($p->harina_real_usada ?? 0);
            $kilos_pagables = max(0.0, $cantidad_kg - $harina_usada);

            // Log para trazabilidad por producción
            try {
                \Illuminate\Support\Facades\Log::info('Panadero::actualizarEstadisticas - produccion', [
                    'panadero_id' => $this->id,
                    'produccion_id' => $p->id,
                    'cantidad_kg' => $cantidad_kg,
                    'harina_real_usada' => $harina_usada,
                    'kilos_pagables' => $kilos_pagables,
                ]);
            } catch (\Throwable $_e) { /* ignore logging failures */ }

            $total_kilos += $kilos_pagables;
        }

        $this->total_kilos_producidos = $total_kilos;
        $this->total_unidades_producidas = $this->producciones()->sum('cantidad_unidades');
        $this->ultima_produccion = $producciones->sortByDesc('fecha_produccion')->first()?->fecha_produccion;
        $this->save();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTurno($query, $turno)
    {
        return $query->where('turno', $turno);
    }

    public function scopePorEspecialidad($query, $especialidad)
    {
        return $query->where('especialidad', $especialidad);
    }
}
