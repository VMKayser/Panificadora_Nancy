<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ConfiguracionSistema;
use App\Models\WhatsAppMessage;

class WhatsAppService
{
    /**
     * Enviar un mensaje de texto vía API de WhatsApp (Facebook Graph API) u otro proveedor configurado.
     * @param string $phone Número telefónico en formato internacional (ej. 5917xxxxxxx) sin signos.
     * @param string $message Texto a enviar.
     * @return array|null Respuesta decoded o null si hubo error.
     */
    public function sendMessage(string $phone, string $message)
    {
        // Normalizar phone
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        if (empty($phoneDigits)) {
            Log::warning('WhatsAppService: número inválido, omitiendo envío.');
            return null;
        }

        // Leer proveedor configurado (por defecto 'facebook' para WhatsApp Cloud API)
        $provider = ConfiguracionSistema::get('whatsapp_provider', 'facebook');

        // Registrar intento en BD
        $log = WhatsAppMessage::create([
            'to_phone' => $phoneDigits,
            'message' => $message,
            'status' => 'pending'
        ]);

        try {
            if ($provider === 'facebook') {
                $token = ConfiguracionSistema::get('whatsapp_api_token', null);
                $phoneNumberId = ConfiguracionSistema::get('whatsapp_phone_number_id', null);

                if (empty($token) || empty($phoneNumberId)) {
                    Log::warning('WhatsAppService: credenciales de Facebook/WhatsApp no configuradas.');
                    return null;
                }

                $url = "https://graph.facebook.com/v15.0/{$phoneNumberId}/messages";

                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneDigits,
                    'type' => 'text',
                    'text' => ['body' => $message]
                ];

                $resp = Http::withToken($token)
                    ->acceptJson()
                    ->post($url, $payload);

                if ($resp->successful()) {
                    Log::info("WhatsAppService: mensaje enviado a {$phoneDigits}");
                    $log->update(['status' => 'sent', 'response' => json_encode($resp->json()), 'sent_at' => now()]);
                    return $resp->json();
                }

                Log::error('WhatsAppService: error al enviar mensaje', ['status' => $resp->status(), 'body' => $resp->body()]);
                $log->update(['status' => 'failed', 'response' => $resp->body()]);
                return $resp->json();
            } else {
                // Implementación para otros proveedores: configurar provider-specific endpoint y token
                $apiUrl = ConfiguracionSistema::get('whatsapp_api_url', null);
                $apiToken = ConfiguracionSistema::get('whatsapp_api_token', null);
                if (empty($apiUrl)) {
                    Log::warning('WhatsAppService: proveedor personalizado no configurado (whatsapp_api_url vacío)');
                    return null;
                }

                $resp = Http::withToken($apiToken)->post($apiUrl, [
                    'to' => $phoneDigits,
                    'message' => $message
                ]);

                if ($resp->successful()) {
                    Log::info("WhatsAppService: (provider={$provider}) mensaje enviado a {$phoneDigits}");
                    $log->update(['status' => 'sent', 'response' => json_encode($resp->json()), 'sent_at' => now()]);
                    return $resp->json();
                }

                Log::error('WhatsAppService: error proveedor personalizado', ['status' => $resp->status(), 'body' => $resp->body()]);
                $log->update(['status' => 'failed', 'response' => $resp->body()]);
                return $resp->json();
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppService exception: ' . $e->getMessage());
            if (isset($log)) {
                $log->update(['status' => 'failed', 'response' => $e->getMessage()]);
            }
            return null;
        }
    }
}
