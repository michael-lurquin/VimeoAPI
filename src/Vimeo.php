<?php

namespace MichaelLurquin\Vimeo;

use Illuminate\Support\Collection;

// https://adevait.com/laravel/how-to-create-a-custom-package-for-laravel
// https://laravelpackage.com

/**
 * @see https://developer.vimeo.com/api/reference
 */
class Vimeo extends BaseVimeo
{
    protected $clientID;
    protected $clientSecret;
    protected $accessToken;
    protected $client;

    public function __construct()
    {
        $this->accessToken = config('vimeo.access_token');

        if ( !empty($this->accessToken) ) $this->client = $this->prepareRequestFromAccessTokenAuthentication();

        $this->clientID = config('vimeo.client_id');
        $this->clientSecret = config('vimeo.client_secret');

        if ( !empty($clientID) && !empty($clientSecret) ) $this->client = $this->prepareRequestFromBasicAuthentication();

        if ( empty($this->client) ) abort(401, 'No "client_id" and/or "client_secret" configured!');

        $this->setHeaders();

        $this->forUser(null);

        return $this;
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
     * @return self
     */
    public function specification() : self
    {
        $this->clearEndpoint();

        $this->setEndpoint('/');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Get capabilities of user's
     *
     * @method GET
     * @link https://api.vimeo.com/{user_id}/capabilities
     *
     * @return self
     */
    public function capabilities() : self
    {
        $this->setEndpoint('/capabilities');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Get the user
     * 
     * > This method returns the authenticated user.
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}
     * @see https://developer.vimeo.com/api/reference/users#get_user
     *
     * @return self
     */
    public function me() : self
    {
        $this->setEndpoint('');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
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
    public function checkValidateToken() : self
    {
        $this->clearEndpoint();

        $this->setEndpoint('/oauth/verif');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
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
    public function deleteToken() : self
    {
        $this->setEndpoint('/tokens');
        $this->setMethod('DELETE');
        $this->setBody([]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Get quotas
     *  
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}?fields=upload_quota.space
     * @see https://developer.vimeo.com/api/reference/users#get_user
     */
    public function getQuotaOfStorage() : Collection
    {
        $this->setEndpoint('/');
        $this->setMethod('GET');
        $this->setBody([]);
        $this->columns(['upload_quota.space']);
        $this->setKeyOfCollection('upload_quota.space');
        $this->setOnlyOfCollection(['free', 'max', 'used']);

        $to = 1099511627776;

        $response = $this->request();

        return new Collection([
            'free' => round((int) $response->get('free') / $to, 2) . ' TB',
            'used' => round((int) $response->get('used') / $to, 2) . ' TB',
            'max' => round((int) $response->get('max') / $to, 2) . ' TB',
        ]);
    }

    public function folders(string $path = 'root') : self
    {
        $this->setEndpoint("/folders/{$path}");
        $this->setMethod('GET');
        $this->prepareFields([
            'direction' => 'desc',
            'exclude_personal_team_folder' => true,
            'exclude_shared_videos' => false,
            'filter' => 'folder',
        ]);

        return $this;
    }

    /**
     * Get all the videos that the user has uploaded
     * 
     * > This method returns all the videos that the authenticated user has uploaded.
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos
     * @see https://developer.vimeo.com/api/reference/videos#get_videos
     */
    public function videos() : self
    {
        $this->setEndpoint("/videos");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Get a specific video
     * 
     * > This method returns a single video.
     *
     * @param int $videoID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     */
    public function video(int $videoID) : self
    {
        $this->setEndpoint("/videos/{$videoID}");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Edit a video
     * 
     * > This method edits the specified video.
     *
     * @param int $videoID
     * @param array $params
     * 
     * @requires "edit" scope
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#edit_video
     */
    public function editVideo(int $videoID, array $params) : self
    {
        $this->setEndpoint("/videos/{$videoID}");
        $this->setMethod('PATCH');
        $this->setBody($params);

        return $this;
    }

    /**
     * Delete a video
     * 
     * > This method deletes the specified video. The authenticated user must be the owner of the video.
     *
     * @param int $videoID
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/videos#delete_video
     *
     * @return bool
     */
    public function deleteVideo(int $videoID) : self
    {
        $this->setEndpoint("/videos/{$videoID}");
        $this->setMethod('DELETE');
        $this->setBody([]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Search a video
     * 
     * > This method edits the specified video.
     *
     * @param string $query
     * 
     * @method GET
     * @link https://api.vimeo.com/videos
     * @see https://developer.vimeo.com/api/reference/videos#search_videos
     */
    public function searchVideosAllVimeo(string $query) : self
    {
        $this->clearEndpoint();

        $this->setEndpoint('/videos');
        $this->setMethod('GET');
        $this->setBody([]);
        $this->setQuery($query);

        return $this;
    }

    /**
     * Get thumbnail of video
     *
     * @param int $videoID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?sizes=1920x1080&fields=pictures.sizes.link
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     */
    public function thumbnailOfVideo(int $videoID) : self
    {
        $this->setEndpoint("/videos/{$videoID}?sizes=1920x1080&fields=pictures.sizes.link");
        $this->setMethod('GET');
        $this->setBody([]);
        $this->setKeyOfCollection('pictures.sizes.0.link');
        $this->setGetOfCollection(0);

        return $this;
    }

    /**
     * Get download links of video
     *
     * @param int $videoID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?fields=files
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     */
    public function downloadLinksOfVideo(int $videoID) : self
    {
        $this->setEndpoint("/videos/{$videoID}");
        $this->setMethod('GET');
        $this->setBody([]);
        $this->columns(['files']);

        return $this;
    }

    /**
     * Get stats of video (only "plays" count for all days)
     *
     * @param int $videoID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/:user/videos/:video?fields=stats
     * @see https://developer.vimeo.com/api/reference/videos#get_video
     */
    public function statisticsOfVideo(int $videoID) : self
    {
        $this->setEndpoint("/videos/{$videoID}");
        $this->setMethod('GET');
        $this->setBody([]);
        $this->columns(['stats']);
        $this->setKeyOfCollection('stats.plays');
        $this->setGetOfCollection(0);

        return $this;
    }

    /**
     * Get all the live events that belong to the user
     * 
     * > The method returns every live event belonging to the authenticated user.
     *
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#get_live_events
     */
    public function streams() : self
    {
        $this->setEndpoint('/live_events');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Create a live event
     * 
     * > This method creates a new live event for the authenticated user.
     *
     * @param string $title
     * @param array $params
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#create_live_event
     */
    public function createStream(string $title, array $params = []) : self
    {
        $this->setEndpoint('/live_events');
        $this->setMethod('POST');
        $this->setBody([
            'title' => $title,
            'automatically_title_stream' => true,
        ] + $params);

        return $this;
    }

    /**
     * Update a live event
     * 
     * > This method updates a live event belonging to the authenticated user.
     *
     * @param int $liveID
     * @param array $params
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & edit" scopes
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/live_events
     * @see https://developer.vimeo.com/api/reference/live#update_live_event
     */
    public function editStream(int $liveID, array $params) : self
    {
        $this->setEndpoint("/live_events/{$liveID}");
        $this->setMethod('PATCH');
        $this->setBody($params);

        return $this;
    }

    /**
     * Delete a live event
     * 
     * > This method updates a live event belonging to the authenticated user.
     *
     * @param int $liveID
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & delete" scopes
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}
     * @see https://developer.vimeo.com/api/reference/live#delete_live_event
     * 
     * @return bool
     */
    public function deleteStream(int $liveID) : self
    {
        $this->setEndpoint("/live_events/{$liveID}");
        $this->setMethod('DELETE');
        $this->setBody([]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Get a specific live event
     * 
     * > This method returns a single live event belonging to the authenticated user.
     *
     * @param int $liveID
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private" scope
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}
     * @see https://developer.vimeo.com/api/reference/live#get_live_event
     */
    public function stream(int $liveID) : self
    {
        $this->setEndpoint("/live_events/{$liveID}");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Get status of live event
     *
     * @param int $sessionID
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/videos/{video_id}/sessions/status
     * @see https://developer.vimeo.com/api/reference/live#get_live_event
     */
    public function statusOfStream(int $sessionID) : self
    {
        $this->setEndpoint("/videos/{$sessionID}/sessions/status");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Activate a live event
     * 
     * > This method creates the necessary RTMP links for the specified live event. Begin streaming to these links to trigger the live event on Vimeo. The authenticated user must be the owner of the event.
     *
     * @param int $liveID
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}/activate
     * @see https://developer.vimeo.com/api/reference/live#activate_live_event
     */
    public function startStream(int $liveID) : self
    {
        $this->setEndpoint("/live_events/{$liveID}/activate");
        $this->setMethod('POST');
        $this->setBody([]);
        $this->setReturnResponseCode(200);

        return $this;
    }

    /**
     * End a live event
     * 
     * > This method ends the specified live event. The authenticated user must be the owner of the event.
     *
     * @param int $liveID
     * 
     * @requires capability : CAPABILITY_RECURRING_LIVE_EVENTS
     * @requires "private & create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/live_events/{live_event_id}/end
     * @see https://developer.vimeo.com/api/reference/live#end_live_event
     */
    public function stopStream(int $liveID) : self
    {
        $this->setEndpoint("/live_events/{$liveID}/end");
        $this->setMethod('POST');
        $this->setBody([]);
        $this->setReturnResponseCode(200);

        return $this;
    }
}