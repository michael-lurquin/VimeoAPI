<?php

namespace MichaelLurquin\Vimeo\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * @see https://developer.vimeo.com/api/authentication
 */
class VimeoOAuthTest extends TestCase
{
    private $headers;
    private $response;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('vimeo.method', 'oauth');

        $this->headers = $app['config']->get('headers');
        $this->response = $app['config']->get('response');
    }

    /**
     * @test
     * @see https://developer.vimeo.com/api/reference/authentication-extras#client_auth
     */
    public function get_access_token()
    {
        Http::fake([
            config('vimeo.authenticate') => Http::response($this->response, 200, $this->headers),
        ]);

        $response = Http::withBasicAuth(
            config('vimeo.app_id'),
            config('vimeo.app_secret')
        )
            ->withHeaders($this->headers)
            ->post(config('vimeo.authenticate'), [
                'grant_type' => 'client_credentials',
                'scope' => $this->response['scope'],
            ])
        ;

        Http::assertSent(function(Request $request) {
            return 
                $request->hasHeader('Content-Type', $this->headers['Content-Type']) &&
                $request->hasHeader('Accept', $this->headers['Accept']) &&
                $request->url() === config('vimeo.authenticate')
            ;
        });

        $data = $response->collect();

        $this->assertEquals(200, $response->status());
        $this->assertEquals('oauth', config('vimeo.method'));
        $this->assertEquals(config('vimeo.token'), $data->get('access_token'));
        $this->assertEquals($this->response['token_type'], $data->get('token_type'));
        $this->assertEquals($this->response['scope'], $data->get('scope'));
        $this->assertEquals($this->response['app']['name'], $data->get('app')['name']);
        $this->assertEquals($this->response['app']['uri'], $data->get('app')['uri']);
    }

    /**
     * @test
     * @see https://developer.vimeo.com/api/reference/authentication-extras#verify_token
     */
    public function verification_token()
    {
        Http::fake([
            config('vimeo.verification') => Http::response($this->response, 200, $this->headers),
        ]);

        $response = Http::withHeaders($this->headers)
            ->withToken(config('vimeo.token'))
            ->get(config('vimeo.verification'))
        ;

        Http::assertSent(function(Request $request) {
            return 
                $request->hasHeader('Content-Type', $this->headers['Content-Type']) &&
                $request->hasHeader('Accept', $this->headers['Accept']) &&
                $request->hasHeader('Authorization', 'Bearer ' . config('vimeo.token')) &&
                $request->url() === config('vimeo.verification')
            ;
        });

        $data = $response->collect();

        $this->assertEquals(200, $response->status());
        $this->assertEquals('oauth', config('vimeo.method'));
        $this->assertEquals(config('vimeo.token'), $data->get('access_token'));
        $this->assertEquals($this->response['token_type'], $data->get('token_type'));
        $this->assertEquals($this->response['scope'], $data->get('scope'));
        $this->assertEquals($this->response['app']['name'], $data->get('app')['name']);
        $this->assertEquals($this->response['app']['uri'], $data->get('app')['uri']);
    }
}