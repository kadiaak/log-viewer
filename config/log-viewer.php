<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Viewer enabled
    |--------------------------------------------------------------------------
    |
    | Set this to "false" to completely disable the package (routes, assets and
    | all). Handy if you want to make sure it never loads in production.
    |
    */

    'enabled' => env('LOG_VIEWER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | The UI and its JSON API are served under this prefix. You may also attach
    | any middleware you need (auth, custom gates, etc.).
    |
    */

    'route' => [
        'prefix' => env('LOG_VIEWER_PREFIX', 'log-viewer'),
        'domain' => env('LOG_VIEWER_DOMAIN'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Back to system URL
    |--------------------------------------------------------------------------
    |
    | Shown in the header so users can navigate back to your application.
    | Set to null to hide the link.
    |
    */

    'back_to_system_url' => env('LOG_VIEWER_BACK_TO_SYSTEM_URL', config('app.url')),
    'back_to_system_label' => null,

    /*
    |--------------------------------------------------------------------------
    | Where to look for log files
    |--------------------------------------------------------------------------
    |
    | A list of glob patterns. Every file matching any of the "include" patterns
    | (and none of the "exclude" patterns) will be listed in the viewer.
    |
    */

    'include_files' => [
        storage_path('logs/**/*.log'),
        storage_path('logs/*.log'),
    ],

    'exclude_files' => [
        // storage_path('logs/sensitive.log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum log file size (in bytes) that will be parsed
    |--------------------------------------------------------------------------
    |
    | Files bigger than this are still listed (and can be downloaded), but the
    | viewer refuses to parse them in-browser to avoid memory issues. Set to 0
    | to disable the limit entirely.
    |
    */

    'max_log_size' => 150 * 1024 * 1024, // 150 MB

    /*
    |--------------------------------------------------------------------------
    | Allow destructive actions
    |--------------------------------------------------------------------------
    |
    | Enables the "clear" and "delete" buttons for log files from the UI.
    |
    */

    'allow_delete' => env('LOG_VIEWER_ALLOW_DELETE', true),
    'allow_download' => env('LOG_VIEWER_ALLOW_DOWNLOAD', true),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | Default UI theme: "system", "light" or "dark".
    |
    */

    'theme' => env('LOG_VIEWER_THEME', 'system'),

];
