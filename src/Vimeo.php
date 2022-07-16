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

    /**
     * Get all the folders that belong to the user"
     *
     * > This method returns all the folders belonging to the authenticated user.
     *
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects
     * @see https://developer.vimeo.com/api/reference/folders#get_projects
     */
    public function folders() : self
    {
        $this->setEndpoint('/projects');
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Get a specific folder
     * 
     * > This method returns a single folder belonging to the authenticated user.
     * 
     * @param int $folderID
     * 
     * @requires "private" scope
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#get_projects
     */
    public function folder(int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Create a folder
     * 
     * > This method creates a new folder for the authenticated user.
     *
     * @param string $name
     * 
     * @requires "create" scope
     * @method POST
     * @link https://api.vimeo.com/users/{user_id}/projects
     * @see https://developer.vimeo.com/api/reference/folders#create_project
     */
    public function createFolder(string $name) : self
    {
        $this->setEndpoint('/projects');
        $this->setMethod('POST');
        $this->setBody([
            'name' => $name,
        ]);

        return $this;
    }

    /**
     * Edit a folder
     * 
     * > This method edits the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param int $folderID
     * @param string $name
     * 
     * @requires "edit" scope
     * @method PATCH
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#edit_project
     */
    public function editFolder(int $folderID, string $name) : self
    {
        $this->setEndpoint("/projects/{$folderID}");
        $this->setMethod('PATCH');
        $this->setBody([
            'name' => $name,
        ]);

        return $this;
    }

    /**
     * Delete a folder
     * 
     * > This method deletes the specified folder and optionally also the videos that it contains. The authenticated user must be the owner of the folder.
     *
     * @param int $folderID
     * @param bool $deleteAllVideos
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}
     * @see https://developer.vimeo.com/api/reference/folders#delete_project
     */
    public function deleteFolder(int $folderID, bool $deleteAllVideos = false) : self
    {
        $this->setEndpoint("/projects/{$folderID}");
        $this->setMethod('DELETE');
        $this->setBody([
            'should_delete_clips' => $deleteAllVideos,
        ]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Get all the videos in a folder
     * 
     * > This method returns all the videos that belong to the specified folder.
     *
     * @param int $folderID
     * 
     * @method GET
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#get_project_videos
     */
    public function videosOfFolder(int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}/videos");
        $this->setMethod('GET');
        $this->setBody([]);

        return $this;
    }

    /**
     * Add a specific video to a folder
     * 
     * > This method adds a single video to the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param int $videoID
     * @param int $folderID
     * 
     * @requires "interact" scope
     * @method PUT
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/folders#get_project_videos
     */
    public function addVideoToFolder(int $videoID, int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}/videos/{$videoID}");
        $this->setMethod('GET');
        $this->setBody([]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Add a list of videos to a folder
     * 
     * > This method adds multiple videos to the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param array $videoIDs
     * @param int $folderID
     * 
     * @requires "interact" scope
     * @method PUT
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#add_videos_to_project
     */
    public function addVideosToFolder(array $videoIDs, int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}/videos");
        $this->setMethod('PUT');
        $this->setBody([
            'uris' => implode(',', $videoIDs),
        ]);
        $this->setReturnResponseCode(204);
        
        return $this;
    }

    /**
     * Remove a specific video from a folder
     * 
     * > This method removes a single video from the specified folder. Note that this will not delete the video itself.
     *
     * @param int $videoID
     * @param int $folderID
     * 
     * @requires "delete" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos/{video_id}
     * @see https://developer.vimeo.com/api/reference/folders#remove_video_from_project
     */
    public function deleteVideoFromFolder(int $videoID, int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}/videos/$videoID}");
        $this->setMethod('DELETE');
        $this->setBody([]);
        $this->setReturnResponseCode(204);

        return $this;
    }

    /**
     * Remove a list of videos from a folder
     * 
     * > This method removes multiple videos from the specified folder. The authenticated user must be the owner of the folder.
     *
     * @param array $videoIDs
     * @param int $folderID
     * 
     * @requires "interact" scope
     * @method DELETE
     * @link https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos
     * @see https://developer.vimeo.com/api/reference/folders#remove_videos_from_project
     */
    public function deleteVideosFromFolder(array $videoIDs, int $folderID) : self
    {
        $this->setEndpoint("/projects/{$folderID}/videos");
        $this->setMethod('DELETE');
        $this->setBody([
            'uris' => implode(',', $videoIDs),
        ]);
        $this->setReturnResponseCode(204);

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