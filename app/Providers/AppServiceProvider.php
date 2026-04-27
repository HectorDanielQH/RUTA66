<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
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
        FilamentAsset::register([
            Css::make('leaflet-styles')
                ->relativePublicPath('vendor/leaflet/leaflet.css'),
            Js::make('leaflet-scripts', asset('vendor/leaflet/leaflet.js'))
                ->loadedOnRequest(false),
        ]);
    }
}
