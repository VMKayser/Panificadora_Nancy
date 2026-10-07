<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Parámetros de listados (orden y paginación) validados con lista blanca y tope,
 * para que sort_by / sort_order / per_page no provoquen 500 ni volcados masivos.
 */
trait ListadoSeguro
{
    /** @var array<string, string[]> */
    private static array $columnasPorTabla = [];

    /**
     * @return array{0: string, 1: string} [columna, dirección]
     */
    protected function ordenSeguro(Request $request, string $tabla, string $columnaDefecto, string $direccionDefecto = 'asc'): array
    {
        $columna = (string) $request->get('sort_by', $columnaDefecto);
        if (!in_array($columna, $this->columnasDe($tabla), true)) {
            $columna = $columnaDefecto;
        }

        $direccion = strtolower((string) $request->get('sort_order', $direccionDefecto));
        if (!in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = $direccionDefecto;
        }

        return [$columna, $direccion];
    }

    protected function porPagina(Request $request, int $defecto = 15, int $maximo = 1000): int
    {
        $valor = (int) $request->get('per_page', $defecto);
        return $valor > 0 ? min($valor, $maximo) : $defecto;
    }

    private function columnasDe(string $tabla): array
    {
        return self::$columnasPorTabla[$tabla] ??= Schema::getColumnListing($tabla);
    }
}
