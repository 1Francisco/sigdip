<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;

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
        Paginator::useBootstrapFive();

        // Trust all proxies dynamically when running behind Cloudflare Tunnel, ngrok, or Railway
        if ($this->app->request->header('x-forwarded-proto') === 'https' || $this->app->request->secure()) {
            \Illuminate\Http\Request::setTrustedProxies(
                [$this->app->request->getClientIp()],
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );

            // Force HTTPS for all generated URLs and assets
            \Illuminate\Support\Facades\URL::forceScheme('https');

            // Dynamically mark session cookie as Secure
            config(['session.secure' => true]);
        }
    }
}
