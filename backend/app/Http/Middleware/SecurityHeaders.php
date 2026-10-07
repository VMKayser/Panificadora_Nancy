<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas del backend.
 * Se registra como middleware global en bootstrap/app.php.
 * Las de la SPA (HTML estático) se configuran en frontend/public/.htaccess.
 */
class SecurityHeaders
{
    /** La API solo devuelve datos: no carga recursos ni se embebe en marcos. */
    private const CSP_API = "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'";

    /** Páginas HTML del backend (p. ej. /app): solo recursos propios. */
    private const CSP_HTML = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self'; "
        . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $esHtml = str_contains((string) $response->headers->get('Content-Type'), 'text/html');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $esHtml ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        if (!$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $esHtml ? self::CSP_HTML : self::CSP_API);
        }
        if ($request->isSecure()) {
            // Sin includeSubDomains: hay subdominios (api., www.) gestionados por Hostinger.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // No anunciar la tecnología ni la versión de PHP.
        $response->headers->remove('X-Powered-By');
        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
