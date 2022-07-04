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
        $this->app->bind('vimeo', function ($app) {
            return new Vimeo;
        });
    }
}