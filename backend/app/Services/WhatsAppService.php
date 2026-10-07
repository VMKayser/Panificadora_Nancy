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
                if (!preg_match('/^\d{5,30}$/', (string) $phoneNumberId)) {
                    Log::warning('WhatsAppService: whatsapp_phone_number_id inválido, omitiendo envío.');
                    $log->update(['status' => 'failed', 'response' => 'phone_number_id inválido']);
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
                    ->timeout(15)
                    ->withoutRedirecting()
                    ->post($url, $payload);

                if ($resp->successful()) {
                    Log::info('WhatsAppService: mensaje enviado a ' . self::enmascarar($phoneDigits));
                    $log->update(['status' => 'sent', 'response' => self::recortar(json_encode($resp->json())), 'sent_at' => now()]);
                    return $resp->json();
                }

                Log::error('WhatsAppService: error al enviar mensaje', ['status' => $resp->status(), 'body' => self::recortar($resp->body())]);
                $log->update(['status' => 'failed', 'response' => self::recortar($resp->body())]);
                return $resp->json();
            } else {
                // Implementación para otros proveedores: configurar provider-specific endpoint y token
                $apiUrl = ConfiguracionSistema::get('whatsapp_api_url', null);
                $apiToken = ConfiguracionSistema::get('whatsapp_api_token', null);
                if (empty($apiUrl)) {
                    Log::warning('WhatsAppService: proveedor personalizado no configurado (whatsapp_api_url vacío)');
                    return null;
                }

                // La URL viene de la configuración: solo HTTPS, hosts permitidos y nunca
                // direcciones internas (evita SSRF hacia la red del servidor).
                if (!self::urlSalientePermitida((string) $apiUrl)) {
                    Log::channel('security')->warning('whatsapp.url_bloqueada', ['host' => parse_url((string) $apiUrl, PHP_URL_HOST)]);
                    $log->update(['status' => 'failed', 'response' => 'URL del proveedor no permitida']);
                    return null;
                }

                $resp = Http::withToken($apiToken)
                    ->timeout(15)
                    ->withoutRedirecting()
                    ->post($apiUrl, [
                        'to' => $phoneDigits,
                        'message' => $message
                    ]);

                if ($resp->successful()) {
                    Log::info("WhatsAppService: (provider={$provider}) mensaje enviado a " . self::enmascarar($phoneDigits));
                    $log->update(['status' => 'sent', 'response' => self::recortar(json_encode($resp->json())), 'sent_at' => now()]);
                    return $resp->json();
                }

                Log::error('WhatsAppService: error proveedor personalizado', ['status' => $resp->status(), 'body' => self::recortar($resp->body())]);
                $log->update(['status' => 'failed', 'response' => self::recortar($resp->body())]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppService exception: ' . $e->getMessage());
            if (isset($log)) {
                $log->update(['status' => 'failed', 'response' => self::recortar($e->getMessage())]);
            }
            return null;
        }
    }

    /**
     * Solo HTTPS, sin credenciales en la URL, host en la lista permitida y que no
     * resuelva a direcciones privadas, loopback, link-local ni reservadas.
     */
    public static function urlSalientePermitida(string $url): bool
    {
        $partes = parse_url($url);
        if (!$partes || strtolower($partes['scheme'] ?? '') !== 'https' || empty($partes['host'])
            || isset($partes['user']) || isset($partes['pass'])) {
            return false;
        }
        if (isset($partes['port']) && (int) $partes['port'] !== 443) {
            return false;
        }

        $host = strtolower(trim($partes['host'], '[]'));
        $permitidos = array_map('strtolower', config('services.whatsapp.allowed_hosts', []));
        $permitidos[] = 'graph.facebook.com';
        if (!in_array($host, $permitidos, true)) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $registro) {
            if (!empty($registro['ipv6'])) {
                $ips[] = $registro['ipv6'];
            }
        }
        if (empty($ips)) {
            return false;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        return true;
    }

    private static function enmascarar(string $telefono): string
    {
        return strlen($telefono) > 4 ? str_repeat('*', strlen($telefono) - 4) . substr($telefono, -4) : '****';
    }

    private static function recortar(?string $texto, int $max = 1000): ?string
    {
        return $texto === null ? null : mb_substr($texto, 0, $max);
    }
}
