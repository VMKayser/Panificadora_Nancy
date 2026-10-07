<?php

namespace App\Support;

class AssetUrl
{
    /**
     * Normaliza URLs de assets públicos para evitar rutas relativas o dominios duplicados.
     */
    public static function normalize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return $url;
        }

        $baseUrl = rtrim(config('app.url'), '/');

        // Si la cadena contiene dos URLs completas del mismo dominio concatenadas, elimina la primera.
        if (preg_match('#^(https?://[^/]+)(https?://.+)$#i', $url, $matches)) {
            $firstHost = parse_url($matches[1], PHP_URL_HOST);
            $secondHost = parse_url($matches[2], PHP_URL_HOST);
            if ($firstHost && $secondHost && strcasecmp($firstHost, $secondHost) === 0) {
                $url = $matches[2];
            }
        }

        // Si no comienza con http/https, asumir que es relativo al dominio del backend.
        if (!preg_match('#^https?://#i', $url)) {
            $path = ltrim($url, '/');
            $url = $baseUrl . '/' . $path;
        }

        // Si después de las transformaciones quedó nuevamente con doble dominio, recortar.
        $doublePrefix = $baseUrl . $baseUrl;
        if (str_starts_with($url, $doublePrefix)) {
            $url = $baseUrl . substr($url, strlen($doublePrefix));
        }

        return $url;
    }
}
