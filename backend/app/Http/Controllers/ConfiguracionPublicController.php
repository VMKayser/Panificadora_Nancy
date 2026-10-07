<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use App\Support\AssetUrl;
use App\Support\Miniaturas;

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
            'qr_mensaje_plantilla',
            'nombre_empresa'
        ];

        if (!in_array($clave, $whitelist)) {
            return response()->json(['message' => 'Clave no pública'], 403);
        }

        $config = ConfiguracionSistema::where('clave', $clave)->first();

        if (!$config) {
            return response()->json([
                'clave' => $clave,
                'valor' => null,
                'tipo' => 'string',
                'existe' => false,
                'message' => 'Configuración no encontrada',
            ], 200);
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

        $valor = $this->normalizeIfAsset($config->clave, $valor);

        $respuesta = [
            'clave' => $config->clave,
            'valor' => $valor,
            'tipo' => $config->tipo,
            'existe' => true,
        ];

        // El logo se muestra chico en la cabecera: se ofrece su versión reducida
        if ($config->clave === 'logo_url' && is_string($valor)) {
            $respuesta['miniatura'] = Miniaturas::url($valor, Miniaturas::MINIATURA);
            $respuesta['mediana'] = Miniaturas::url($valor, Miniaturas::MEDIANA);
        }

        return response()->json($respuesta);
    }

    private function normalizeIfAsset(string $clave, $valor)
    {
        if (!is_string($valor)) {
            return $valor;
        }

        $keys = ['logo_url', 'qr_pago_url'];

        if (in_array($clave, $keys, true)) {
            return AssetUrl::normalize($valor);
        }

        return $valor;
    }
}
