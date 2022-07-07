<?php

namespace MichaelLurquin\Vimeo;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

// https://adevait.com/laravel/how-to-create-a-custom-package-for-laravel
// https://laravelpackage.com

/**
 * @see https://developer.vimeo.com/api/reference
 * @see CAPABILITIES : https://api.vimeo.com/users/:user/capabilities
 */
class Vimeo
{
    private $accessToken;
    private $keyCacheToken = 'vimeo-token';
    private Collection $scopes;
    private int $perPage = 25;

    public function __construct()
    {
        $this->scopes = collect(config('vimeo.scopes', []));
        $this->setCacheForToken(config('vimeo.token'));

        if ( config('vimeo.method') === 'oauth' )
        {
            $response = Http::withBasicAuth(
                config('vimeo.app_id'),
                config('vimeo.app_secret')
            )
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
                ])
                ->post(config('vimeo.authenticate'), [
                    'grant_type' => 'client_credentials',
                    'scope' => implode(' ', config('vimeo.scopes')),
                ])
                ->collect()
            ;

            $this->setCacheForToken((string) $response->get('access_token'));
            $this->scopes = collect(explode(' ', (string) $response->get('scope', [])));
        }

        $this->accessToken = Cache::get($this->keyCacheToken);
    }

    private function setCacheForToken(string $token)
    {
        Cache::put($this->keyCacheToken, $token, config('vimeo.cache', 60 * 24) * 60);
    }

    /**
     * Get the API specification
     * 
     * > This method returns the full OpenAPI specification for the Vimeo API.
     *
     * @requires capability: CAPABILITY_VIEW_CUSTOM_OPEN_API_TAGS
     * @method GET
     * @link https://api.vimeo.com/
     * @see https://developer.vimeo.com/api/reference/api-information#get_endpoints
     *
     * @return Collection
     */
    public function getSpecification(array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Get capabilities of user's
     * 
     * @param string|null $userID
     *
     * @method GET
     * @link https://api.vimeo.com/{user_id}/capabilities
     *
     * @return Collection
     */
    public function getCapabilities(string $userID = null) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/capabilities" : "/users/{$userID}/capabilities";

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Get the user
     * 
     * > This method returns the authenticated user.
     * 
     * @param string|null $userID
     * @param array $fields
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}
     * @see https://developer.vimeo.com/api/reference/users#get_user
     *
     * @return Collection
     */
    public function getMe(string $userID = null, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me" : "/users/{$userID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Check validate access token
     *
     * @method GET
     * @link https://api.vimeo.com/oauth/verify
     * @see https://developer.vimeo.com/api/reference/authentication-extras#verify_token
     *
     * @return bool
     */
    public function checkValidateToken() : bool
    {
        $endpoint = config('vimeo.endpoint') . '/oauth/verify';

        return Http::withToken($this->accessToken)->get($endpoint)->collect('access_token')->get(0) === $this->accessToken;
    }

    /**
     * Revoke the current access token
     * 
     * > This method revokes the access token that the requesting app is currently using. The token must be of the OAuth 2 type.
     *
     * @method DELETE
     * @link https://api.vimeo.com/tokens
     * @see https://developer.vimeo.com/api/reference/authentication-extras#delete_token
     *
     * @return bool
     */
    public function deleteToken() : bool
    {
        $endpoint = config('vimeo.endpoint') . '/tokens';

        return Http::withToken($this->accessToken)->delete($endpoint)->status() === 204;
    }

    /**
     * Get quotas
     *
     * @param string|null $userID
     *  
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}?fields=upload_quota.space
     * @see https://developer.vimeo.com/api/reference/users#get_user
     *
     * @return Collection
     */
    public function getQuotaOfStorage(string $userID = null) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me" : "/users/{$userID}";
        
        $endpoint .= '?fields=upload_quota.space';

        $to = 1099511627776;

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect('upload_quota.space')->only(['free', 'max', 'used']);

        return new Collection([
            'free' => round((int) $response->get('free') / $to, 2) . ' TB',
            'used' => round((int) $response->get('used') / $to, 2) . ' TB',
            'max' => round((int) $response->get('max') / $to, 2) . ' TB',
        ]);
    }

    /**
     * Get all the folders that belong to the user"
     *
     * > This method returns all the folders belonging to the authenticated user.
     * 
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires "private" scope
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects
     * @see https://developer.vimeo.com/api/reference/folders#get_projects
     *
     * @return Collection
     */
    public function getFolders(string $userID = null, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('private') ) abort(403, 'Missing scope : private');

        $fields = ['name', 'created_time', 'uri'];

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects" : "/users/{$userID}/projects";

        $endpoint .= '?fields=' . implode(',', $fields);

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect();

        $countPages = (int) $response->get('total') / (int) $response->get('per_page', $this->perPage);

        $data = new Collection($response->get('data'));

        if ( $countPages > 1 )
        {
            $endpoints = [];

            for ($i = 2; $i <= $countPages; $i++) $endpoints[] = $endpoint . "&page={$i}";

            (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                return (new Collection($endpoints))->map(function($endpoint) use($pool) {
                    return $pool->withToken($this->accessToken)->get($endpoint);
                })->toArray();
            })))->each(function($response) use(&$data) {
                $data = $data->merge($response->collect('data'));
            });
        }

        return $data;
    }

    /**
     * Get a specific folder
     * 
     * > This method returns a single folder belonging to the authenticated user.
     * 
     * @param string $folderID
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires "private" scope
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#get_projects
     *
     * @return Collection
     */
    public function getFolder(string $folderID, string $userID = null, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('private') ) abort(403, 'Missing scope : private');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}" : "/users/{$userID}/projects/{$folderID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Create a folder
     * 
     * > This method creates a new folder for the authenticated user.
     *
     * @param string $name
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires "create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/projects
     * @see https://developer.vimeo.com/api/reference/folders#create_project
     *
     * @return Collection
     */
    public function createFolder(string $name, string $userID = null, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('create') ) abort(403, 'Missing scope : create');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects" : "/users/{$userID}/projects";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->post($endpoint, [
            'name' => $name,
        ])->collect();
    }

    /**
     * Edit a folder
     * 
     * > This method edits the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param string $folderID
     * @param string $name
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires "edit" scope
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#edit_project
     *
     * @return Collection
     */
    public function editFolder(string $folderID, string $name, string $userID = null, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('edit') ) abort(403, 'Missing scope : edit');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}" : "/users/{$userID}/projects/{$folderID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->patch($endpoint, [
            'name' => $name,
        ])->collect();
    }

    /**
     * Delete a folder
     * 
     * > This method deletes the specified folder and optionally also the videos that it contains. The authenticated user must be the owner of the folder.
     *
     * @param string $folderID
     * @param string|null $userID
     * @param bool $deleteAllVideos
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#delete_project
     *
     * @return bool
     */
    public function deleteFolder(string $folderID, bool $deleteAllVideos = false, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('delete') ) abort(403, 'Missing scope : delete');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}" : "/users/{$userID}/projects/{$folderID}";

        return Http::withToken($this->accessToken)->delete($endpoint, [
            'should_delete_clips' => $deleteAllVideos,
        ])->status() === 204;
    }

    /**
     * Get all the videos in a folder
     * 
     * > This method returns all the videos that belong to the specified folder.
     *
     * @param string $folderID
     * @param string|null $userID
     * @param array $fields
     * 
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#get_project_videos
     *
     * @return Collection
     */
    public function getVideosOfFolder(string $folderID, string $userID = null, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}/videos" : "/users/{$userID}/projects/{$folderID}/videos";

        $endpoint .= '?fields=' . implode(',', $fields);

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect();

        $countPages = (int) $response->get('total') / (int) $response->get('per_page', $this->perPage);

        $data = new Collection($response->get('data'));

        if ( $countPages > 1 )
        {
            $endpoints = [];

            for ($i = 2; $i <= $countPages; $i++) $endpoints[] = $endpoint . "&page={$i}";

            (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                return (new Collection($endpoints))->map(function($endpoint) use($pool) {
                    return $pool->withToken($this->accessToken)->get($endpoint);
                })->toArray();
            })))->each(function($response) use(&$data) {
                $data = $data->merge($response->collect('data'));
            });
        }

        return $data;
    }

    /**
     * Add a specific video to a folder
     * 
     * > This method adds a single video to the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param string $videoID
     * @param string $folderID
     * @param string|null $userID
     * 
     * @requires "interact" scope
     * @method PUT
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/folders#get_project_videos
     *
     * @return Collection
     */
    public function addVideoToFolder(string $videoID, string $folderID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('interact') ) abort(403, 'Missing scope : interact');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}/videos/{$videoID}" : "/users/{$userID}/projects/{$folderID}/videos/{$videoID}";

        return Http::withToken($this->accessToken)->put($endpoint)->status() === 204;
    }

    /**
     * Add a list of videos to a folder
     * 
     * > This method adds multiple videos to the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param array $videoIDs
     * @param string $folderID
     * @param string|null $userID
     * 
     * @requires "interact" scope
     * @method PUT
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#add_videos_to_project
     *
     * @return Collection
     */
    public function addVideosToFolder(array $videoIDs, string $folderID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('interact') ) abort(403, 'Missing scope : interact');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}/videos" : "/users/{$userID}/projects/{$folderID}/videos";

        return Http::withToken($this->accessToken)->put($endpoint, [
            'uris' => implode(',', $videoIDs),
        ])->status() === 204;
    }

    /**
     * Remove a specific video from a folder
     * 
     * > This method removes a single video from the specified folder. Note that this will not delete the video itself.
     *
     * @param string $videoID
     * @param string $folderID
     * @param string|null $userID
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/folders#remove_video_from_project
     *
     * @return Collection
     */
    public function deleteVideoFromFolder(string $videoID, string $folderID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('delete') ) abort(403, 'Missing scope : delete');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}/videos/{$videoID}" : "/users/{$userID}/projects/{$folderID}/videos/{$videoID}";

        return Http::withToken($this->accessToken)->delete($endpoint)->status() === 204;
    }

    /**
     * Remove a list of videos from a folder
     * 
     * > This method removes multiple videos from the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param array $videoIDs
     * @param string $folderID
     * @param string|null $userID
     * 
     * @requires "interact" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#remove_videos_from_project
     *
     * @return Collection
     */
    public function deleteVideosFromFolder(array $videoIDs, string $folderID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('interact') ) abort(403, 'Missing scope : interact');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/projects/{$folderID}/videos" : "/users/{$userID}/projects/{$folderID}/videos";

        return Http::withToken($this->accessToken)->delete($endpoint, [
            'uris' => implode(',', $videoIDs),
        ])->status() === 204;
    }

    /**
     * Get all the videos that the user has uploaded
     * 
     * > This method returns all the videos that the authenticated user has uploaded.
     *
     * @param string|null $userID
     * @param array $fields
     * 
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos
     * @see https://developer.vimeo.com/api/reference/videos#get_videos
     *
     * @return Collection
     */
    public function getAllVideos(string $userID = null, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos" : "/users/{$userID}/videos";

        $endpoint .= '?fields=' . implode(',', $fields);

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect();

        $countPages = (int) $response->get('total') / (int) $response->get('per_page', $this->perPage);

        $data = new Collection($response->get('data'));

        if ( $countPages > 1 )
        {
            $endpoints = [];

            for ($i = 2; $i <= $countPages; $i++) $endpoints[] = $endpoint . "&page={$i}";

            (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                return (new Collection($endpoints))->map(function($endpoint) use($pool) {
                    return $pool->withToken($this->accessToken)->get($endpoint);
                })->toArray();
            })))->each(function($response) use(&$data) {
                $data = $data->merge($response->collect('data'));
            });
        }

        return $data;
    }

    /**
     * Get a specific video
     * 
     * > This method returns a single video.
     *
     * @param string $videoID
     * @param string|null $userID
     * @param array $fields
     * 
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     *
     * @return Collection
     */
    public function getVideo(string $videoID, string $userID = null, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Edit a video
     * 
     * > This method edits the specified video.
     *
     * @param string $videoID
     * @param string|null $userID
     * @param array $params
     * @param array $fields
     * 
     * @requires "edit" scope
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#edit_video
     *
     * @return Collection
     */
    public function editVideo(string $videoID, string $userID = null, array $params, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('edit') ) abort(403, 'Missing scope : edit');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->patch($endpoint, $params)->collect();
    }

    /**
     * Delete a video
     * 
     * > This method deletes the specified video. The authenticated user must be the owner of the video.
     *
     * @param string $videoID
     * @param string|null $userID
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#delete_video
     *
     * @return bool
     */
    public function deleteVideo(string $videoID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('delete') ) abort(403, 'Missing scope : delete');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        return Http::withToken($this->accessToken)->delete($endpoint)->status() === 204;
    }

    /**
     * Search a video
     * 
     * > This method edits the specified video.
     *
     * @param string $query
     * @param array $fields
     * 
     * @method GET
     * @link https://api.vimeo.com/videos
     * @see https://developer.vimeo.com/api/reference/videos#search_videos
     *
     * @return Collection
     */
    public function searchVideosAllVimeo(string $query, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint') . '/videos';

        $endpoint .= '?query=' . $query;

        $endpoint .= '&fields=' . implode(',', $fields);

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect();

        $countPages = (int) $response->get('total') / (int) $response->get('per_page', $this->perPage);

        $data = new Collection($response->get('data'));

        if ( $countPages > 1 )
        {
            $endpoints = [];

            for ($i = 2; $i <= $countPages; $i++) $endpoints[] = $endpoint . "&page={$i}";

            (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                return (new Collection($endpoints))->map(function($endpoint) use($pool) {
                    return $pool->withToken($this->accessToken)->get($endpoint);
                })->toArray();
            })))->each(function($response) use(&$data) {
                $data = $data->merge($response->collect('data'));
            });
        }

        return $data;
    }

    /**
     * Get thumbnail of video
     *
     * @param string $videoID
     * @param string|null $userID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?sizes=1920x1080&fields=pictures.sizes.link
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     *
     * @return string
     */
    public function getThumbnailOfVideo(string $videoID, string $userID = null) : string
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        $endpoint .= '?sizes=1920x1080&fields=pictures.sizes.link';

        return (string) Http::withToken($this->accessToken)->get($endpoint)->collect('pictures.sizes.0.link')->get(0);
    }

    /**
     * Get download links of video
     *
     * @param string $videoID
     * @param string|null $userID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?fields=files
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     *
     * @return Collection
     */
    public function getDownloadLinksOfVideo(string $videoID, string $userID = null) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        $endpoint .= '?fields=files';

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Get stats of video (only "plays" count for all days)
     *
     * @param string $videoID
     * @param string|null $userID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?fields=stats
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     *
     * @return Collection
     */
    public function getStatisticsOfVideo(string $videoID, string $userID = null) : int
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/videos/{$videoID}" : "/users/{$userID}/videos/{$videoID}";

        $endpoint .= '?fields=stats';

        return (int) Http::withToken($this->accessToken)->get($endpoint)->collect('stats.plays')->get(0);
    }

    /**
     * Get all the live events that belong to the user
     * 
     * > The method returns every live event belonging to the authenticated user.
     *
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#get_live_events
     * 
     * @return Collection
     */
    public function getAllStreams(string $userID = null, array $fields = []) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events" : "/users/{$userID}/live_events";

        $endpoint .= '?fields=' . implode(',', $fields);

        $response = Http::withToken($this->accessToken)->get($endpoint)->collect();

        $countPages = (int) $response->get('total') / (int) $response->get('per_page', $this->perPage);

        $data = new Collection($response->get('data'));

        if ( $countPages > 1 )
        {
            $endpoints = [];

            for ($i = 2; $i <= $countPages; $i++) $endpoints[] = $endpoint . "&page={$i}";

            (new Collection(Http::pool(function(Pool $pool) use($endpoints) {
                return (new Collection($endpoints))->map(function($endpoint) use($pool) {
                    return $pool->withToken($this->accessToken)->get($endpoint);
                })->toArray();
            })))->each(function($response) use(&$data) {
                $data = $data->merge($response->collect('data'));
            });
        }

        return $data;
    }

    /**
     * Create a live event
     * 
     * > This method creates a new live event for the authenticated user.
     *
     * @param string $title
     * @param string|null $userID
     * @param array $params
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#create_live_event
     * 
     * @return Collection
     */
    public function createStream(string $title, string $userID = null, array $params = [], array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('create') ) abort(403, 'Missing scope : create');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events" : "/users/{$userID}/live_events";

        $fields = array_merge(['title', 'uri', 'rtmps_link', 'stream_key', 'metadata.connections.pre_live_video.uri'], $fields);

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->post($endpoint, $params + [
            'title' => $title,
            'automatically_title_stream' => true,
        ])->collect();
    }

    /**
     * Update a live event
     * 
     * > This method updates a live event belonging to the authenticated user.
     *
     * @param string $liveID
     * @param string|null $userID
     * @param array $params
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & edit" scopes
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#update_live_event
     * 
     * @return Collection
     */
    public function editStream(string $liveID, string $userID = null, array $params, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('private') ) abort(403, 'Missing scope : private');
        if ( !$this->scopes->contains('edit') ) abort(403, 'Missing scope : edit');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events/{$liveID}" : "/users/{$userID}/live_events/{$liveID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->patch($endpoint, $params)->collect();
    }

    /**
     * Delete a live event
     * 
     * > This method updates a live event belonging to the authenticated user.
     *
     * @param string $liveID
     * @param string|null $userID
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & delete" scopes
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}
     * @see https://developer.vimeo.com/api/reference/live#delete_live_event
     * 
     * @return bool
     */
    public function deleteStream(string $liveID, string $userID = null) : bool
    {
        if ( !$this->scopes->contains('private') ) abort(403, 'Missing scope : private');
        if ( !$this->scopes->contains('delete') ) abort(403, 'Missing scope : delete');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events/{$liveID}" : "/users/{$userID}/live_events/{$liveID}";

        return Http::withToken($this->accessToken)->delete($endpoint)->status() === 204;
    }

    /**
     * Get a specific live event
     * 
     * > This method returns a single live event belonging to the authenticated user.
     *
     * @param string $liveID
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private" scope
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}
     * @see https://developer.vimeo.com/api/reference/live#get_live_event
     * 
     * @return Collection
     */
    public function getStream(string $liveID, string $userID = null, array $fields = []) : Collection
    {
        if ( !$this->scopes->contains('private') ) abort(403, 'Missing scope : private');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events/{$liveID}" : "/users/{$userID}/live_events/{$liveID}";

        $endpoint .= '?fields=' . implode(',', $fields);

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Get status of live event
     *
     * @param string $videoID
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}/sessions/status
     * @see https://developer.vimeo.com/api/reference/live#get_live_event
     * 
     * @return Collection
     */
    public function getStatusOfStream(string $videoID) : Collection
    {
        $endpoint = config('vimeo.endpoint');

        $endpoint .= "/videos/{$videoID}/sessions/status";

        return Http::withToken($this->accessToken)->get($endpoint)->collect();
    }

    /**
     * Activate a live event
     * 
     * > This method creates the necessary RTMP links for the specified live event. Begin streaming to these links to trigger the live event on Vimeo. The authenticated user must be the owner of the event.
     *
     * @param string $liveID
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}/activate
     * @see https://developer.vimeo.com/api/reference/live#activate_live_event
     * 
     * @return bool
     */
    public function startStream(string $liveID, string $userID = null) : bool
    {
        if (!$this->scopes->contains('private')) abort(403, 'Missing scope : private');
        if (!$this->scopes->contains('create')) abort(403, 'Missing scope : create');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events/{$liveID}/activate" : "/users/{$userID}/live_events/{$liveID}/activate";

        return Http::withToken($this->accessToken)->post($endpoint)->status() === 200;
    }

    /**
     * End a live event
     * 
     * > This method ends the specified live event. The authenticated user must be the owner of the event.
     *
     * @param string $liveID
     * @param string|null $userID
     * @param array $fields
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}/end
     * @see https://developer.vimeo.com/api/reference/live#end_live_event
     * 
     * @return bool
     */
    public function stopStream(string $liveID, string $userID = null) : bool
    {
        if (!$this->scopes->contains('private')) abort(403, 'Missing scope : private');
        if (!$this->scopes->contains('create')) abort(403, 'Missing scope : create');

        $endpoint = config('vimeo.endpoint');

        $endpoint .= empty($userID) ? "/me/live_events/{$liveID}/end" : "/users/{$userID}/live_events/{$liveID}/end";

        return Http::withToken($this->accessToken)->post($endpoint)->status() === 200;
    }
}