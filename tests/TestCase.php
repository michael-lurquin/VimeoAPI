<?php

namespace MichaelLurquin\Vimeo\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use MichaelLurquin\Vimeo\VimeoServiceProvider;

abstract class TestCase extends Orchestra
{
    public function setUp() : void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            VimeoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('vimeo.method', 'token');

        $app['config']->set('vimeo.authenticate', 'https://api.vimeo.com/oauth/authorize/client');
        $app['config']->set('vimeo.endpoint', 'https://api.vimeo.com');
        $app['config']->set('vimeo.verification', 'https://api.vimeo.com/oauth/verify');

        $app['config']->set('vimeo.app_id', '1234');
        $app['config']->set('vimeo.app_secret', '1234567890');
        $app['config']->set('vimeo.user_id', '0123');
        $app['config']->set('vimeo.token', 'my_token');

        $app['config']->set('vimeo.scopes', [
            'private',
            'purchased',
            'create',
            'edit',
            'delete',
            'interact',
            'promo_codes',
            'stats',
            'video_files',
            'public',
        ]);

        $app['config']->set('vimeo.cache', 60 * 24);

        $app['config']->set('headers', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
        ]);

        $app['config']->set('response', [
            'access_token' => config('vimeo.token'),
            'token_type' => 'bearer',
            'scope' => config('vimeo.scopes'),
            'app' => [
                'name' =>  'My App',
                'uri' => '/apps/' . config('vimeo.app_id'),
            ],
        ]);
    }
}