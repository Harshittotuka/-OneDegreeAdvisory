<?php

namespace App\Providers;

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
        // In production every generated URL (canonical tags, sitemap, OG/JSON-LD
        // @ids) must share ONE origin, otherwise Google sees www + non-www +
        // http + https as competing duplicates and splits ranking signals.
        // APP_URL is the single source of truth for that canonical origin; we
        // force the scheme and root so url()/route() never echo back a stray
        // host from the incoming request (e.g. www when canonical is non-www).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            $appUrl = (string) config('app.url');
            if ($appUrl !== '') {
                URL::forceRootUrl($appUrl);
            }
        }
    }
}
