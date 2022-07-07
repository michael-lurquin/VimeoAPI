<?php

return [
    /*
     * "token" or "oauth"
     */
    'method' => 'token',

    'authenticate' => 'https://api.vimeo.com/oauth/authorize/client',
    'endpoint' => 'https://api.vimeo.com',
    'verification' => 'https://api.vimeo.com/oauth/verify',

    'app_id' => env('VIMEO_APP_ID'),
    'app_secret' => env('VIMEO_APP_SECRET'),
    'user_id' => env('VIMEO_USER_ID'),
    'token' => env('VIMEO_TOKEN'),
    'scopes' => [
        'create',
        'delete',
        'edit',
        'private',
        'public',
        'stats',
        'upload',
        'video_file',
    ],

    // "scopes" => [
    //     "create" => "Create new albums, channels, and so on",
    //     "delete" => "Delete videos, albums, channels, and so on",
    //     "edit" => "Edit existing videos, albums, channels, and so on",
    //     "email" => "Access to email addresses",
    //     "interact" => "Interact with Vimeo resources on a member's behalf, such as liking a video or following another member",
    //     "private" => "Access private member data",
    //     "promo_codes" => "Add, remove, and review Vimeo On Demand promotions",
    //     "public" => "Access public member data",
    //     "purchase" => "Purchase content",
    //     "purchased" => "Access a member's Vimeo On Demand purchase history",
    //     "scim" => "Manage users and team groups via the SCIM protocol",
    //     "stats" => "Access video stats",
    //     "upload" => "Upload videos",
    //     "video_files" => "Access video files belonging to members with a PRO subscription or higher"
    // ]

    'cache' => 60 * 24,
];