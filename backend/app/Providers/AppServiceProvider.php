<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Producto;
use App\Observers\ProductoObserver;
use App\Models\Pedido;
use App\Observers\PedidoObserver;
use App\Http\Middleware\SecurityHeaders;

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
        
        // Register SecurityHeaders middleware into the 'api' middleware group if router is available
        // and define a sensible rate limiter for API routes.
        $this->app->afterResolving(Router::class, function (Router $router) {
            // Prepend to ensure security headers run early for API responses
            try {
                $router->prependMiddlewareToGroup('api', SecurityHeaders::class);
            } catch (\Throwable $e) {
                // Fallback: push if prepend not available
                $router->pushMiddlewareToGroup('api', SecurityHeaders::class);
            }
        });

        // Define API rate limiter: 60 requests per minute per user or IP
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key);
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
}
