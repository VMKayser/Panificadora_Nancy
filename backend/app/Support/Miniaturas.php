<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Versiones reducidas en WebP de las imágenes subidas (fotos de productos y
 * logo). Las fotos originales pesan ~330 KB y en las tarjetas se ven a unos
 * 180 px: la tienda pide la miniatura (480 px) o la mediana (960 px) y usa el
 * original si la versión reducida no existe.
 */
class Miniaturas
{
    public const MINIATURA = 480;
    public const MEDIANA = 960;

    private const CARPETA = 'productos/miniaturas';
    private const CALIDAD = 78;

    /** Ruta en el disco public de la versión reducida de "productos/abc.jpg". */
    public static function ruta(string $rutaOriginal, int $ancho): string
    {
        return self::CARPETA . '/' . pathinfo($rutaOriginal, PATHINFO_FILENAME) . "-{$ancho}.webp";
    }

    /**
     * Crea las versiones que falten de una imagen del disco public. Devuelve
     * cuántas creó. No lanza excepciones: si falla, la tienda usa el original.
     */
    public static function generar(string $rutaOriginal, bool $forzar = false): int
    {
        $disco = Storage::disk('public');
        $archivo = $disco->path($rutaOriginal);
        if (!is_file($archivo) || !function_exists('imagewebp')) {
            return 0;
        }

        $anchos = array_filter(
            [self::MINIATURA, self::MEDIANA],
            fn (int $ancho) => $forzar || !$disco->exists(self::ruta($rutaOriginal, $ancho))
        );
        if (!$anchos) {
            return 0;
        }

        try {
            $imagen = self::abrir($archivo);
            if (!$imagen) {
                return 0;
            }
            $disco->makeDirectory(self::CARPETA);

            $anchoOriginal = imagesx($imagen);
            $altoOriginal = imagesy($imagen);
            $creadas = 0;
            foreach ($anchos as $ancho) {
                // Nunca se agranda: una foto chica queda de su tamaño, pero en WebP
                $w = min($ancho, $anchoOriginal);
                $h = max(1, (int) round($altoOriginal * $w / $anchoOriginal));
                $lienzo = imagecreatetruecolor($w, $h);
                imagealphablending($lienzo, false);
                imagesavealpha($lienzo, true);
                imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $w, $h, $anchoOriginal, $altoOriginal);
                if (imagewebp($lienzo, $disco->path(self::ruta($rutaOriginal, $ancho)), self::CALIDAD)) {
                    $creadas++;
                }
                imagedestroy($lienzo);
            }
            imagedestroy($imagen);

            return $creadas;
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar la miniatura', ['archivo' => $rutaOriginal, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /** URL pública de la versión reducida de una imagen, o null si todavía no existe. */
    public static function url(?string $urlOriginal, int $ancho): ?string
    {
        $ruta = self::rutaDesdeUrl($urlOriginal);
        if (!$ruta) {
            return null;
        }
        $reducida = self::ruta($ruta, $ancho);

        return Storage::disk('public')->exists($reducida)
            ? AssetUrl::normalize(Storage::disk('public')->url($reducida))
            : null;
    }

    /** "https://api…/storage/productos/abc.jpg" -> "productos/abc.jpg" */
    public static function rutaDesdeUrl(?string $url): ?string
    {
        $path = $url ? (parse_url($url, PHP_URL_PATH) ?: '') : '';

        return preg_match('#/storage/(productos/[^/]+\.(?:jpe?g|png|webp))$#i', $path, $m) ? $m[1] : null;
    }

    /** @return \GdImage|null */
    private static function abrir(string $archivo)
    {
        $info = @getimagesize($archivo);
        if (!$info) {
            return null;
        }
        $imagen = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($archivo),
            IMAGETYPE_PNG => @imagecreatefrompng($archivo),
            IMAGETYPE_WEBP => @imagecreatefromwebp($archivo),
            default => false,
        };
        if (!$imagen) {
            return null;
        }

        // Las fotos de celular guardan el giro en EXIF y el WebP no lo conserva
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($archivo);
            $grados = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($grados && ($girada = imagerotate($imagen, $grados, 0))) {
                imagedestroy($imagen);
                $imagen = $girada;
            }
        }

        return $imagen;
    }
}
