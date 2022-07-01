<?php

namespace MichaelLurquin\VimeoApi;

use Illuminate\Support\ServiceProvider;

class VimeoProvider extends ServiceProvider
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