<?php

namespace MichaelLurquin\Vimeo;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BaseVimeo
{
    protected int|null $userID;
    private array $fields = [];
    private string $endpoint = '';
    private string $method = 'GET';
    private array $body = [];
    private string|null $query = null;
    private int|null $returnCode = null;
    private string|null $keyOfCollection = null;
    private array|null $onlyOfCollection = null;
    private string|null $getOfCollection = null;
    private int $perPage = 25;

    protected function setHeaders() : void
    {
        $this->client->contentType(config('vimeo.endpoints.headers')['Content-Type']);
        $this->client->accept(config('vimeo.endpoints.headers')['Accept']);
    }

    protected function prepareRequestFromBasicAuthentication()
    {
        return Http::vimeo()->withBasicAuth($this->clientID, $this->clientSecret);
    }

    protected function prepareRequestFromAccessTokenAuthentication()
    {
        return Http::vimeo()->withToken($this->accessToken);
    }

    public function forUser(int $userID = null) : self
    {
        $this->userID = $userID;

        $this->endpoint = !empty($this->userID) ? "/users/{$this->userID}" : '/me';

        return $this;
    }

    public function columns(array $fields = []) : self
    {
        $this->fields = $fields;

        return $this;
    }

    protected function prepareFields() : string
    {
        $symbol = is_null($this->query) ? '?' : '&';

        return !empty($this->fields) ? "{$symbol}fields=" . implode(',', $this->fields) : '';
    }

    protected function setMethod(string $method) : void
    {
        $this->method = strtoupper($method);
    }

    protected function setBody(array $body) : void
    {
        $this->body = $body;
    }

    protected function setEndpoint(string $endpoint) : void
    {
        $this->endpoint .= $endpoint;
    }

    protected function setQuery(string $query) : void
    {
        $this->query = $query;
    }

    protected function clearEndpoint()
    {
        $this->endpoint = '';
    }

    protected function setReturnResponseCode(int $code)
    {
        $this->returnCode = $code;
    }

    public function dd() : self
    {
        $this->client = $this->client->dd();

        return $this;
    }

    protected function setKeyOfCollection(string $key)
    {
        $this->keyOfCollection = $key;
    }

    protected function setOnlyOfCollection(array $fields)
    {
        $this->onlyOfCollection = $fields;
    }

    protected function setGetOfCollection(string $get)
    {
        $this->getOfCollection = $get;
    }

    protected function clear() : void
    {
        $this->fields = [];
        $this->endpoint = '';
        $this->method = 'GET';
        $this->body = [];
        $this->userID = null;
        $this->returnBoolean = false;
        $this->keyOfCollection = null;
        $this->onlyOfCollection = null;
        $this->getOfCollection = null;
        $this->query = null;
    }

    protected function request() : Collection|bool|string
    {
        $endpoint = is_null($this->query) ? $this->endpoint : $this->endpoint . "?query={$this->query}";

        $endpoint = $endpoint . $this->prepareFields();

        switch ($this->method) {
            case 'GET':
                $response = $this->client->get($endpoint);
                break;

            case 'POST':
                $response = $this->client->post($endpoint, $this->body);
                break;

            case 'PATCH':
                $response = $this->client->patch($endpoint, $this->body);
                break;

            case 'PUT':
                $response = $this->client->put($endpoint, $this->body);
                break;

            case 'DELETE':
                $response = $this->client->delete($endpoint, $this->body);
                break;
        }

        if ( !is_null($this->returnCode) ) return (int) $response->status() === $this->returnCode;
        else
        {
            $data = !is_null($this->keyOfCollection) ? $response->collect($this->keyOfCollection) : $response->collect();

            $data = $this->hasMultiplePages($data, config('vimeo.endpoints.base') . $endpoint);
            
            if ( !is_null($this->onlyOfCollection) ) $data = $data->only($this->onlyOfCollection);

            if ( !is_null($this->getOfCollection) ) $data = $data->get($this->getOfCollection);

            return $data;
        }
    }

    private function hasMultiplePages(Collection $data, string $endpoint)
    {
        if ( $data->has('total') && $data->has('per_page') && $data->has('data') )
        {
            $tmp = new Collection();

            $countPages = (int) $data->get('total') / (int) $data->get('per_page', $this->perPage);

            if ( $countPages > 1 )
            {
                $data = new Collection($data->get('data'));

                $endpoints = [];

                for ($i = 1; $i <= $countPages; $i++) $endpoints[] = $endpoint . '&page=' . ( $i + 1 );

                (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                    return (new Collection($endpoints))->map(function($value) use($pool) {
                        return $pool->withToken($this->accessToken)->get($value);
                    })->toArray();
                })))->each(function($response) use(&$data) {
                    $data = $data->merge($response->collect('data'));
                });

                $tmp = $data;
            }
            else $tmp = new Collection($data->get('data'));

            return $tmp;
        }
        else return $data;
    }

    public function get() : Collection|bool|string
    {
        $response = $this->request();

        $this->clear();

        return $response;
    }
}