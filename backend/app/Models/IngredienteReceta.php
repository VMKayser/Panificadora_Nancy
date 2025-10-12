<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredienteReceta extends Model
{
    protected $table = 'ingredientes_receta';

    protected $fillable = [
        'receta_id',
        'materia_prima_id',
        'cantidad',
        'unidad',
        'costo_calculado',
        'orden',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_calculado' => 'decimal:2',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }

    public function materiaPrima()
    {
        return $this->belongsTo(MateriaPrima::class);
    }
}
