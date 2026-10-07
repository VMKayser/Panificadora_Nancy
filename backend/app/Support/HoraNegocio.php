<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * La app guarda los timestamps en UTC, pero el negocio opera en hora de Bolivia.
 * Estas utilidades dan "ahora" y "hoy" en la zona del negocio.
 */
class HoraNegocio
{
    public static function zona(): string
    {
        return config('app.business_timezone', 'America/La_Paz');
    }

    /** Hora local de Bolivia expresada como fecha "naive" (misma convención que fecha_entrega). */
    public static function ahoraLocal(): Carbon
    {
        return Carbon::parse(now(self::zona())->format('Y-m-d H:i:s'));
    }

    public static function hoy(): string
    {
        return now(self::zona())->toDateString();
    }

    /**
     * Rango [inicio, fin] en UTC del día local indicado, para filtrar columnas
     * como created_at que se guardan en UTC.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function rangoUtcDelDia(?string $fecha = null): array
    {
        $dia = Carbon::parse($fecha ?? self::hoy(), self::zona());

        return [
            $dia->copy()->startOfDay()->utc(),
            $dia->copy()->endOfDay()->utc(),
        ];
    }
}
