<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add common security headers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Basic Content Security Policy; adjust as needed for inline scripts/styles
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; connect-src 'self' https:;");
        // Referrer policy
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        // XSS filter (legacy)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        // HSTS for HTTPS sites (only if served over HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        return $response;
    }
}
