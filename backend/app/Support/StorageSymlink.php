<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class StorageSymlink
{
    /**
     * Avoid running the expensive checks on every request.
     */
    protected static bool $checkedDefaultPaths = false;

    /**
     * Ensure the public/storage symlink exists (or any custom pair provided).
     */
    public static function ensure(
        ?Filesystem $filesystem = null,
        ?string $publicLink = null,
        ?string $target = null
    ): void {
        $filesystem ??= app(Filesystem::class);
        $publicLink ??= public_path('storage');
        $target ??= storage_path('app/public');

        $usingDefaults = $publicLink === public_path('storage') && $target === storage_path('app/public');
        if ($usingDefaults && self::$checkedDefaultPaths) {
            return;
        }

        if (! self::needsLink($filesystem, $publicLink, $target, $usingDefaults)) {
            if ($usingDefaults) {
                self::$checkedDefaultPaths = true;
            }
            return;
        }

        try {
            self::createLink($filesystem, $publicLink, $target);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear el enlace simbólico de storage', [
                'link' => $publicLink,
                'target' => $target,
                'error' => $e->getMessage(),
            ]);
        }

        if ($usingDefaults) {
            self::$checkedDefaultPaths = true;
        }
    }

    protected static function needsLink(Filesystem $filesystem, string $publicLink, string $target, bool $logWarnings): bool
    {
        if (is_link($publicLink)) {
            $existing = readlink($publicLink);
            if ($existing !== false && self::pathsMatch($existing, $target)) {
                return false;
            }

            $filesystem->delete($publicLink);
            return true;
        }

        if ($filesystem->exists($publicLink)) {
            if ($logWarnings) {
                Log::warning('El directorio public/storage existe pero no es un enlace simbólico. No se modificará automáticamente.', [
                    'link' => $publicLink,
                ]);
            }
            return false;
        }

        return true;
    }

    protected static function createLink(Filesystem $filesystem, string $publicLink, string $target): void
    {
        if (! $filesystem->exists($target)) {
            $filesystem->makeDirectory($target, 0775, true);
        }

        $filesystem->link($target, $publicLink);
    }

    protected static function pathsMatch(string $existingTarget, string $desiredTarget): bool
    {
        $existingReal = realpath($existingTarget) ?: $existingTarget;
        $desiredReal = realpath($desiredTarget) ?: $desiredTarget;

        return rtrim($existingReal, DIRECTORY_SEPARATOR) === rtrim($desiredReal, DIRECTORY_SEPARATOR);
    }
}
