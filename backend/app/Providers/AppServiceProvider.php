<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Models\User;
use App\Observers\UserObserver;
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
        // Registrar observer para User
        User::observe(UserObserver::class);
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
    }
}
