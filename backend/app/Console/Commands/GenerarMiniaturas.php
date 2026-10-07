<?php

namespace App\Console\Commands;

use App\Support\Miniaturas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerarMiniaturas extends Command
{
    protected $signature = 'imagenes:miniaturas {--forzar : Vuelve a crear las que ya existen}';
    protected $description = 'Crea las versiones reducidas (WebP 480 y 960 px) de las imágenes subidas';

    public function handle()
    {
        $archivos = collect(Storage::disk('public')->files('productos'))
            ->filter(fn ($ruta) => preg_match('/\.(jpe?g|png|webp)$/i', $ruta));

        $creadas = 0;
        foreach ($archivos as $ruta) {
            $creadas += Miniaturas::generar($ruta, (bool) $this->option('forzar'));
        }

        $this->info("{$archivos->count()} imágenes revisadas, {$creadas} versiones reducidas creadas.");
        return 0;
    }
}
