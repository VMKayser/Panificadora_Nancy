<?php

namespace Tests\Feature;

use App\Models\ConfiguracionSistema;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Support\Miniaturas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Versiones reducidas de las fotos para que la tienda no descargue los
 * originales de ~330 KB en cada tarjeta (2026-10-02).
 */
class MiniaturasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD sin soporte WebP en este PHP');
        }
        Storage::fake('public');
    }

    private function fotoDePrueba(string $ruta, int $ancho, int $alto): void
    {
        $img = imagecreatetruecolor($ancho, $alto);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
        Storage::disk('public')->makeDirectory('productos');
        imagejpeg($img, Storage::disk('public')->path($ruta), 90);
        imagedestroy($img);
    }

    public function test_crea_las_dos_versiones_sin_agrandar()
    {
        $this->fotoDePrueba('productos/grande.jpg', 1600, 1200);
        $this->fotoDePrueba('productos/chica.jpg', 600, 400);

        $this->assertSame(2, Miniaturas::generar('productos/grande.jpg'));
        $this->assertSame(2, Miniaturas::generar('productos/chica.jpg'));
        // Una segunda pasada no repite trabajo
        $this->assertSame(0, Miniaturas::generar('productos/grande.jpg'));

        $disco = Storage::disk('public');
        [$w480, $h480] = getimagesize($disco->path('productos/miniaturas/grande-480.webp'));
        [$w960] = getimagesize($disco->path('productos/miniaturas/grande-960.webp'));
        [$wChica] = getimagesize($disco->path('productos/miniaturas/chica-960.webp'));
        $this->assertSame([480, 360], [$w480, $h480]);
        $this->assertSame(960, $w960);
        $this->assertSame(600, $wChica);
    }

    public function test_la_api_publica_entrega_las_versiones_reducidas_y_cae_al_original()
    {
        $this->fotoDePrueba('productos/foto.jpg', 1200, 900);
        Miniaturas::generar('productos/foto.jpg');

        $producto = Producto::factory()->create(['esta_activo' => true]);
        $original = Storage::disk('public')->url('productos/foto.jpg');
        ImagenProducto::create(['producto_id' => $producto->id, 'url_imagen' => $original, 'es_imagen_principal' => true, 'order' => 1]);
        $sinVersiones = Storage::disk('public')->url('productos/sin-versiones.jpg');
        ImagenProducto::create(['producto_id' => $producto->id, 'url_imagen' => $sinVersiones, 'es_imagen_principal' => false, 'order' => 2]);

        $imagenes = collect($this->getJson('/api/productos')->assertOk()->json())
            ->firstWhere('id', $producto->id)['imagenes'];

        $this->assertStringEndsWith('/storage/productos/miniaturas/foto-480.webp', $imagenes[0]['url_miniatura']);
        $this->assertStringEndsWith('/storage/productos/miniaturas/foto-960.webp', $imagenes[0]['url_mediana']);
        $this->assertStringEndsWith('/storage/productos/sin-versiones.jpg', $imagenes[1]['url_miniatura']);
    }

    public function test_el_logo_publico_incluye_su_miniatura()
    {
        $this->fotoDePrueba('productos/logo.jpg', 1600, 1600);
        Miniaturas::generar('productos/logo.jpg');
        ConfiguracionSistema::set('logo_url', Storage::disk('public')->url('productos/logo.jpg'), 'texto');

        $this->getJson('/api/configuraciones/public/logo_url/valor')
            ->assertOk()
            ->assertJsonPath('miniatura', fn ($url) => str_ends_with($url, '/storage/productos/miniaturas/logo-480.webp'));
    }

    public function test_el_comando_procesa_todas_las_imagenes_subidas()
    {
        $this->fotoDePrueba('productos/a.jpg', 1000, 800);
        $this->fotoDePrueba('productos/b.jpg', 1000, 800);

        $this->artisan('imagenes:miniaturas')
            ->expectsOutput('2 imágenes revisadas, 4 versiones reducidas creadas.')
            ->assertExitCode(0);
    }
}
