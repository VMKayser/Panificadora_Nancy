<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Registro de eventos de seguridad en el canal "security" (storage/logs/security-*.log).
 * Cada evento lleva usuario, IP, método, ruta y resultado; nunca contraseñas ni
 * datos personales del cliente.
 */
class SecurityLog
{
    public static function loginFallido(Request $request, string $email, string $motivo): void
    {
        self::registrar('warning', 'auth.login_fallido', $request, [
            'email_hash' => hash('sha256', mb_strtolower(trim($email))),
            'motivo' => $motivo,
        ]);
    }

    public static function loginExitoso(Request $request, $user): void
    {
        self::registrar('info', 'auth.login_exitoso', $request, ['user_id' => $user->id]);
    }

    public static function accesoDenegado(Request $request, string $recurso, array $extra = []): void
    {
        self::registrar('warning', 'acceso.denegado', $request, ['recurso' => $recurso] + $extra);
    }

    public static function limiteExcedido(Request $request): void
    {
        self::registrar('warning', 'abuso.limite_excedido', $request);
    }

    public static function accionSensible(Request $request, string $accion, array $extra = []): void
    {
        self::registrar('notice', 'auditoria.' . $accion, $request, $extra);
    }

    private static function registrar(string $nivel, string $evento, Request $request, array $extra = []): void
    {
        try {
            Log::channel('security')->log($nivel, $evento, array_merge([
                'user_id' => optional($request->user('sanctum'))->id,
                'ip' => $request->ip(),
                'metodo' => $request->method(),
                'ruta' => '/' . ltrim($request->path(), '/'),
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
            ], $extra));
        } catch (\Throwable $e) {
            // Un fallo del log nunca debe romper la petición.
        }
    }
}
