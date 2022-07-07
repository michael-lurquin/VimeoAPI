<?php

namespace MichaelLurquin\Vimeo;

use Illuminate\Support\ServiceProvider;

class VimeoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/vimeo.php' => config_path('vimeo.php'),
        ], 'config');
    }
}