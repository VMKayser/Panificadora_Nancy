<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Support\AssetUrl;
use App\Support\Miniaturas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Catálogo de temporada 2026 (pedido de la panificadora del 2026-10-02):
 * - Navidad: precios de panetón por confirmar, nombres y descripciones
 *   corregidos, caja nueva de Choconan y el juego de cocina como juguete.
 * - Todos Santos: mesa completa, t'anta wawa, piezas sueltas (por par o por
 *   juego, como las vende la panadería) y la cajita para invitar.
 *
 * Se puede correr varias veces: busca por id (panetones) o por url (lo nuevo)
 * y no repite imágenes. Las fotos se leen de storage/app/temporada-2026.
 *
 *   php artisan db:seed --class=Temporada2026Seeder --force
 */
class Temporada2026Seeder extends Seeder
{
    private const CARPETA_FOTOS = 'temporada-2026';
    private const PEDIDOS_HASTA = '2026-10-24';

    private const TRADICIONAL = 'Pan dulce con pasas de uva y fruta abrillantada.';
    private const CHOCONAN = 'Pan dulce con chispas sabor chocolate.';
    private const HORNO = 'Elaborado como en casa, en horno de ladrillo refractario.';

    public function run(): void
    {
        DB::transaction(function () {
            $this->navidad();
            $this->todosSantos();
        });
    }

    private function navidad(): void
    {
        $panetones = [
            1 => ['Panetón tradicional en caja + juguete (700 g)', 'Caja · 700 g + juguete sorpresa', self::TRADICIONAL,
                "Panetón tradicional en caja de regalo, con un juguete sorpresa.\nPeso neto: 700 g."],
            2 => ['Panetón tradicional en caja (700 g)', 'Caja · 700 g', self::TRADICIONAL,
                "Panetón tradicional en caja de regalo (sin juguete).\nPeso neto: 700 g."],
            3 => ['Panetón Choconan en caja + juguete (700 g)', 'Caja · 700 g + juguete sorpresa', self::CHOCONAN,
                "Panetón Choconan en caja de regalo, con un juguete sorpresa.\nPeso neto: 700 g."],
            4 => ['Panetón Choconan en caja (700 g)', 'Caja · 700 g', self::CHOCONAN,
                "Panetón Choconan en caja de regalo (sin juguete).\nPeso neto: 700 g."],
            5 => ['Panetón tradicional en bolsa (700 g)', 'Bolsa zipper · 700 g', self::TRADICIONAL,
                "Panetón tradicional en bolsa zipper.\nPeso neto: 700 g."],
            6 => ['Panetón Choconan en bolsa (700 g)', 'Bolsa zipper · 700 g', self::CHOCONAN,
                "Panetón Choconan en bolsa zipper.\nPeso neto: 700 g."],
            7 => ['Rosca navideña (600 g)', 'Bolsa zipper · 600 g', 'Bizcocho dulce con pasas de uva y fruta abrillantada.',
                "Rosca navideña en bolsa zipper.\nPeso neto: 600 g."],
            8 => ['Mini panetón tradicional + juguete (100 g)', 'Caja · 100 g + juguete sorpresa', self::TRADICIONAL,
                "Mini panetón tradicional en caja, con un juguete sorpresa.\nPeso neto: 100 g."],
            9 => ['Mini panetón Choconan + juguete (100 g)', 'Caja · 100 g + juguete sorpresa', self::CHOCONAN,
                "Mini panetón Choconan en caja, con un juguete sorpresa.\nPeso neto: 100 g."],
            48 => ['Panetón tradicional en bolsa (400 g)', 'Bolsa transparente · 400 g', self::TRADICIONAL,
                "Panetón tradicional en bolsa transparente.\nPeso neto: 400 g."],
        ];

        foreach ($panetones as $id => [$nombre, $presentacion, $corta, $descripcion]) {
            $producto = Producto::find($id);
            if (!$producto) {
                $this->command?->warn("Navidad: no existe el producto {$id}, se omite.");
                continue;
            }
            // La url no se cambia: puede haber enlaces compartidos
            $producto->update([
                'nombre' => $nombre,
                'presentacion' => $presentacion,
                'descripcion_corta' => $corta,
                'descripcion' => $descripcion . "\n" . self::HORNO,
                'precio_por_confirmar' => true,
            ]);
        }

        // Caja nueva de Choconan: reemplaza la foto de la caja vieja
        $cajaChoconan = 'Panetón Choconan en su caja nueva';
        foreach ([3, 4] as $id) {
            if (!Producto::find($id)) {
                continue;
            }
            $yaTiene = ImagenProducto::where('producto_id', $id)->where('texto_alternativo', $cajaChoconan)->exists();
            if (!$yaTiene) {
                ImagenProducto::where('producto_id', $id)->delete();
                $this->agregarImagen($id, 'choconan-caja-700.jpg', $cajaChoconan);
            }
        }

        // El juego de cocina es uno de los juguetes sorpresa
        foreach ([1, 3] as $id) {
            if (Producto::find($id)) {
                $this->agregarImagen($id, 'juguete-cocina.jpg', 'Juguete sorpresa: juego de cocina');
            }
        }
    }

    private function todosSantos(): void
    {
        $categoria = Categoria::firstOrNew(['url' => 'todos-santos']);
        $categoria->fill([
            'nombre' => 'Todos Santos',
            'descripcion' => 'Mesa completa, piezas de masa y masitas para Todos Santos',
            'esta_activo' => true,
        ]);
        $categoria->save();

        // Todos Santos va antes que Navidad en la tienda
        $this->ordenarCategoria($categoria->id, 1);
        if ($navidad = Categoria::where('url', 'navidad')->first()) {
            $this->ordenarCategoria($navidad->id, 2);
        }

        $masitas = collect([
            'Empanaditas de queso',
            'Pancitos',
            'Rollitos de queso',
            'Conitos con dulce de leche',
            'Alfajores de maicena',
            'Donitas',
        ])->map(fn ($nombre) => [
            'nombre' => "{$nombre} (docena)",
            'precio' => 30,
            'descripcion' => '12 unidades · Bs 2,50 c/u',
        ])->all();

        $comun = [
            'categorias_id' => $categoria->id,
            'es_de_temporada' => true,
            'esta_activo' => true,
            'permite_delivery' => true,
            'permite_envio_nacional' => false,
            'requiere_tiempo_anticipacion' => true,
            'tiempo_anticipacion' => 1,
            'unidad_tiempo' => 'semanas',
            'pedidos_hasta' => self::PEDIDOS_HASTA,
            'precio_por_confirmar' => false,
            'precio_mayorista' => null,
            'limite_produccion' => 0,
            'tiene_extras' => false,
            'extras_disponibles' => null,
            'etiqueta_personalizacion' => null,
        ];
        $pieza = 'de masa especial para la mesa de Todos Santos.';

        $productos = [
            [
                'nombre' => 'Mesa completa para difuntos',
                'precio_minorista' => 3000,
                'unidad_medida' => 'paquete',
                'presentacion' => "Con t'anta wawa grande personalizada",
                'descripcion_corta' => 'Todo lo tradicional para la mesa de Todos Santos, elaborado con masa especial.',
                'descripcion' => "Incluye:\n"
                    . "• 1 t'anta wawa tamaño grande (aprox. 90 cm), personalizada con el nombre del difunto\n"
                    . "• 2 acompañantes (hombre y mujer)\n"
                    . "• 1 cruz y 1 escalera\n"
                    . "• 2 lunas, 2 estrellas y 2 soles\n"
                    . "• 2 caballos y 2 camellos\n"
                    . "• 4 esquineros\n"
                    . "• 200 unidades de fruta seca\n"
                    . "• 200 unidades de maicillos\n"
                    . "• 200 unidades de rosquitas\n"
                    . "• 2 arrobas de urpu especial (para la ofrenda)\n\n"
                    . "Las masitas especiales de Royal no están incluidas: puedes agregarlas abajo.\n"
                    . 'El recojo o la entrega se coordina por WhatsApp.',
                'etiqueta_personalizacion' => 'Nombre del difunto',
                'tiene_extras' => true,
                'extras_disponibles' => $masitas,
                'fotos' => [['mesa-completa.jpg', 'Mesa completa de Todos Santos']],
            ],
            [
                'nombre' => "T'anta wawa grande personalizada",
                'precio_minorista' => 250,
                'unidad_medida' => 'unidad',
                'presentacion' => 'Aprox. 90 cm · con el nombre del difunto',
                'descripcion_corta' => 'La figura principal de la mesa de Todos Santos.',
                'descripcion' => "T'anta wawa tamaño grande (aprox. 90 cm), elaborada con masa especial y tradicional "
                    . "y personalizada con el nombre del difunto.\n"
                    . 'El recojo o la entrega se coordina por WhatsApp.',
                'etiqueta_personalizacion' => 'Nombre del difunto',
                'tiene_extras' => true,
                'extras_disponibles' => $masitas,
                'fotos' => [['tanta-wawas.jpg', "T'anta wawas de masa especial"]],
            ],
            [
                'nombre' => 'Cajita para invitar',
                'precio_minorista' => 30,
                'unidad_medida' => 'unidad',
                'presentacion' => '5 masitas + botellita de vino',
                'descripcion_corta' => 'Para invitar a quienes visitan la mesa de Todos Santos.',
                'descripcion' => "Cajita de Todos Santos con 5 masitas especiales de Royal y una pequeña botella de vino tinto.\n"
                    . 'En la cajita escribimos el nombre que nos indiques en el espacio «Para:».',
                'etiqueta_personalizacion' => 'Para',
                'fotos' => [
                    ['cajita-invitar-frente.jpg', 'Cajita para invitar con masitas y vino'],
                    ['cajita-invitar.jpg', 'Cajita para invitar: frente y espacio «Para:»'],
                ],
            ],
            $this->pieza('Acompañantes (par)', 300, 'Hombre y mujer · Bs 150 c/u',
                "Par de acompañantes, hombre y mujer, {$pieza}\nSe venden en par: Bs 150 cada uno.",
                'acompanantes.jpg', 'Acompañantes de masa especial'),
            $this->pieza('Cruz', 70, '1 unidad', "Cruz {$pieza}", 'cruz.jpg', 'Cruz de masa especial'),
            $this->pieza('Escalera', 70, '1 unidad', "Escalera {$pieza}", 'escalera.jpg', 'Escalera de masa especial'),
            $this->pieza('Lunas (par)', 80, 'Par · Bs 40 c/u',
                "Par de lunas {$pieza}\nSe venden en par: Bs 40 cada una.", 'mesa-completa.jpg', 'Luna y estrella sobre la mesa'),
            $this->pieza('Estrellas (par)', 80, 'Par · Bs 40 c/u',
                "Par de estrellas {$pieza}\nSe venden en par: Bs 40 cada una.", 'mesa-completa.jpg', 'Luna y estrella sobre la mesa'),
            $this->pieza('Soles (par)', 80, 'Par · Bs 40 c/u',
                "Par de soles {$pieza}\nSe venden en par: Bs 40 cada uno.", 'soles.jpg', 'Soles de masa especial'),
            $this->pieza('Caballos (par)', 80, 'Par · Bs 40 c/u',
                "Par de caballos {$pieza}\nSe venden en par: Bs 40 cada uno.", 'caballos.jpg', 'Caballos de masa especial'),
            $this->pieza('Camellos (par)', 80, 'Par · Bs 40 c/u',
                "Par de camellos {$pieza}\nSe venden en par: Bs 40 cada uno."),
            $this->pieza('Esquineros (juego de 4)', 160, 'Juego de 4 · Bs 40 c/u',
                "Juego de 4 esquineros {$pieza}\nSe venden por juego de 4: Bs 40 cada uno."),
            $this->pieza('Fruta seca (200 unidades)', 400, '200 unidades · Bs 2 c/u',
                "200 unidades de fruta seca para la mesa de Todos Santos.\nBs 2 cada una."),
            $this->pieza('Maicillos (200 unidades)', 400, '200 unidades · Bs 2 c/u',
                "200 unidades de maicillos para la mesa de Todos Santos.\nBs 2 cada uno."),
            $this->pieza('Rosquitas (200 unidades)', 400, '200 unidades · Bs 2 c/u',
                "200 unidades de rosquitas para la mesa de Todos Santos.\nBs 2 cada una."),
            $this->pieza('Urpu especial (2 arrobas)', 600, '2 arrobas · Bs 300 la arroba',
                "Urpu especial para la ofrenda de Todos Santos.\nSe vende por 2 arrobas: Bs 300 la arroba.", null, null, 'arroba'),
        ];

        foreach ($productos as $datos) {
            $fotos = $datos['fotos'] ?? [];
            unset($datos['fotos']);
            $datos = $datos + $comun;

            $url = Str::slug($datos['nombre']);
            $producto = Producto::withTrashed()->where('url', $url)->first();
            if ($producto?->trashed()) {
                $producto->restore();
            }
            if ($producto) {
                $producto->update($datos);
            } else {
                $producto = Producto::create($datos + ['url' => $url]);
            }

            foreach ($fotos as [$archivo, $alt]) {
                $this->agregarImagen($producto->id, $archivo, $alt);
            }
        }
    }

    private function pieza(string $nombre, int $precio, string $presentacion, string $descripcion,
        ?string $foto = null, ?string $alt = null, string $unidad = 'unidad'): array
    {
        return [
            'nombre' => $nombre,
            'precio_minorista' => $precio,
            'unidad_medida' => $unidad,
            'presentacion' => $presentacion,
            'descripcion_corta' => null,
            'descripcion' => $descripcion . "\nEl recojo o la entrega se coordina por WhatsApp.",
            'fotos' => $foto ? [[$foto, $alt]] : [],
        ];
    }

    /** Las columnas "order" (la que usa la tienda) y "orden" conviven en la tabla. */
    private function ordenarCategoria(int $id, int $orden): void
    {
        $valores = ['order' => $orden];
        if (Schema::hasColumn('categorias', 'orden')) {
            $valores['orden'] = $orden;
        }
        DB::table('categorias')->where('id', $id)->update($valores);
    }

    /**
     * Copia la foto al disco public como lo hace la subida del panel
     * (productos/<hash>.jpg + miniaturas) y la agrega al final de la galería.
     * Si el producto ya tiene una imagen con ese texto alternativo, no hace nada.
     */
    private function agregarImagen(int $productoId, string $archivo, string $alt): void
    {
        if (ImagenProducto::where('producto_id', $productoId)->where('texto_alternativo', $alt)->exists()) {
            return;
        }

        $origen = storage_path('app/' . self::CARPETA_FOTOS . '/' . $archivo);
        if (!is_file($origen)) {
            $this->command?->warn("Falta la foto {$archivo}; el producto {$productoId} queda sin ella.");
            return;
        }

        $ruta = 'productos/' . hash_file('sha256', $origen) . '.' . pathinfo($archivo, PATHINFO_EXTENSION);
        $disco = Storage::disk('public');
        if (!$disco->exists($ruta)) {
            $disco->put($ruta, file_get_contents($origen));
        }
        Miniaturas::generar($ruta);

        $orden = (int) ImagenProducto::where('producto_id', $productoId)->max('order');
        ImagenProducto::create([
            'producto_id' => $productoId,
            'url_imagen' => AssetUrl::normalize(Storage::url($ruta)),
            'texto_alternativo' => $alt,
            'es_imagen_principal' => $orden === 0,
            'order' => $orden + 1,
        ]);
    }
}
