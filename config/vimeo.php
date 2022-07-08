<?php

return [
    'endpoints' => [
        'base' => 'https://api.vimeo.com',

        'headers' => [
            'Accept' => 'application/vnd.vimeo.*+json; version=3.4',
            'Content-Type' => 'application/json',
        ],
    ],

    'client_id' => env('VIMEO_CLIENT_ID'),
    'client_secret' => env('VIMEO_CLIENT_SECRET'),

    'access_token' => env('VIMEO_ACCESS_TOKEN'),

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
    // ],

    'cache' => 60 * 24,
];