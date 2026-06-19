<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
            Request::setTrustedProxies(
                [$this->app->request->getClientIp()],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
            );

            // Force HTTPS for all generated URLs and assets
            URL::forceScheme('https');

            // Dynamically mark session cookie as Secure
            config(['session.secure' => true]);
        }
    }
}
