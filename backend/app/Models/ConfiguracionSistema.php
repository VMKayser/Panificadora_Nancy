<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuracion_sistema';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'grupo'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtener el valor parseado según el tipo
     */
    public function getValorParseadoAttribute()
    {
        switch ($this->tipo) {
            case 'numero':
                return (float) $this->valor;
            case 'boolean':
                return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($this->valor, true);
            default:
                return $this->valor;
        }
    }

    /**
     * Método estático para obtener un valor de configuración
     */
    public static function get($clave, $default = null)
    {
        $config = self::where('clave', $clave)->first();
        
        return $config ? $config->valor_parseado : $default;
    }

    /**
     * Método estático para establecer un valor de configuración
     */
    public static function set($clave, $valor, $tipo = 'texto', $descripcion = null, $grupo = null)
    {
        return self::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'grupo' => $grupo
            ]
        );
    }
}
