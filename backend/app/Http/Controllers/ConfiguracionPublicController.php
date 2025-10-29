<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;

class ConfiguracionPublicController extends Controller
{
    /**
     * Devuelve el valor público de una configuración si está en la lista blanca.
     * Esto permite exponer solo keys no sensibles (logo, QR público, whatsapp, nombre).
     */
    public function getValor($clave)
    {
        $whitelist = [
            'logo_url',
            'qr_pago_url',
            'whatsapp_empresa',
            'nombre_empresa'
        ];

        if (!in_array($clave, $whitelist)) {
            return response()->json(['message' => 'Clave no pública'], 403);
        }

        $config = ConfiguracionSistema::where('clave', $clave)->first();

        if (!$config) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        $valor = $config->valor;

        switch ($config->tipo) {
            case 'numero':
                $valor = (float) $valor;
                break;
            case 'boolean':
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'json':
                $valor = json_decode($valor, true);
                break;
        }

        return response()->json([
            'clave' => $config->clave,
            'valor' => $valor,
            'tipo' => $config->tipo
        ]);
    }
}
