<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Texto de error apto para devolver al cliente.
 *
 * Las reglas de negocio de los modelos lanzan \Exception / \InvalidArgumentException
 * con mensajes pensados para el usuario ("Stock insuficiente de Harina..."); esos se
 * muestran. Cualquier otra excepción (SQL, PDO, errores de PHP) se registra en el log
 * y al cliente solo le llega el texto genérico, para no filtrar consultas ni rutas.
 */
class MensajeError
{
    public static function publico(\Throwable $e, string $generico): string
    {
        if (self::esDeNegocio($e)) {
            return $generico . ': ' . $e->getMessage();
        }

        Log::error($generico . ': ' . $e->getMessage(), ['exception' => $e]);
        return $generico;
    }

    private static function esDeNegocio(\Throwable $e): bool
    {
        return get_class($e) === \Exception::class
            || get_class($e) === \InvalidArgumentException::class
            || str_starts_with(get_class($e), 'App\\Exceptions\\');
    }
}
