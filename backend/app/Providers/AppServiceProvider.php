<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Producto;
use App\Observers\ProductoObserver;
use App\Models\Pedido;
use App\Observers\PedidoObserver;
use App\Support\SecurityLog;
use Illuminate\Cache\RateLimiting\Limit;
use App\Support\StorageSymlink;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers
        User::observe(UserObserver::class);
        Producto::observe(ProductoObserver::class);
        Pedido::observe(PedidoObserver::class);
        StorageSymlink::ensure();
        
        // SecurityHeaders se registra como middleware global en bootstrap/app.php.

        // Límite general de la API (throttleApi en bootstrap/app.php):
        // 120/min por usuario autenticado, 60/min por IP para visitantes.
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user('sanctum');
            return $user
                ? Limit::perMinute(120)->by('user:' . $user->id)->response($this->respuestaLimite(...))
                : Limit::perMinute(60)->by('ip:' . $request->ip())->response($this->respuestaLimite(...));
        });

        // Creación de pedidos desde la tienda (ruta pública): frena el spam de pedidos.
        // El personal (venta de mostrador) no tiene este límite.
        RateLimiter::for('pedidos', function (Request $request) {
            $user = $request->user('sanctum');
            if ($user && $user->hasAnyRole(['admin', 'vendedor'])) {
                return Limit::none();
            }
            return [
                Limit::perMinute(5)->by('pedidos-min:' . $request->ip())->response($this->respuestaLimite(...)),
                Limit::perHour(30)->by('pedidos-hora:' . $request->ip())->response($this->respuestaLimite(...)),
            ];
        });

        // Force a consistent From address to avoid mail providers rewriting it or treating it as spoofing.
        // This helps ensure the From header is the site address (configured in .env) even if a notification
        // or mailer sets a different from. It is safe to call unconditionally.
        try {
            $from = config('mail.from.address');
            $name = config('mail.from.name');
            if (!empty($from)) {
                Mail::alwaysFrom($from, $name ?: null);
            }
        } catch (\Throwable $e) {
            // Don't break the application if mail config is not available at boot time.
        }
    }

    /** Respuesta 429 que además deja constancia en el log de seguridad. */
    private function respuestaLimite(Request $request, array $headers)
    {
        SecurityLog::limiteExcedido($request);
        return response()->json(['message' => 'Demasiadas solicitudes. Espera un momento e intenta de nuevo.'], 429, $headers);
    }
}
