<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dynamic Configurations
    |--------------------------------------------------------------------------
    |
    | Add config filename which you want to use dynamically
    | Except this file, adding dynamic_config in this array will have no effect
    |
    */

    'dynamic_configs' => [
        'mail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration Default Prefix
    |--------------------------------------------------------------------------
    |
    | To get original configuration, add this prefix.
    | config('app.name') -> returns dynamic configuration. [assuming that 'app' is exists in dynamic_configs]
    | config('default.app.name')-> return original configuration. [assuming that 'default' is set as default_prefix]
    |
    */

    'default_prefix' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Delete Absence Config
    |--------------------------------------------------------------------------
    |
    | Delete config which is removed from original configuration
    |
    */

    'delete_absence_config' => false,

    /*
    |--------------------------------------------------------------------------
    | Enable Configuration Cache
    |--------------------------------------------------------------------------
    |
    | Cache dynamic configuration
    |
    */

    'enable_cache' => false,

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache File Name
    |--------------------------------------------------------------------------
    |
    | Cache File Name to store dynamic configuration.
    |
    */

    'cache_file_name' => 'dynamic_config',

    /*
    |--------------------------------------------------------------------------
    | Dynamic configuration should load at startup or not
    |--------------------------------------------------------------------------
    |
    | If you enable this feature.
    | Dynamic configurations will load at startup.
    | As usual dynamic config loads at application boot time.
    | So, if you want to control core configurations like timezone, view path etc.
    | you have to this.
    |
    |  WARNING : If you enable this feature. Configuration will always remain cache by default.
    |   After enabling this, if you change anything in your configuration file which is being controlled
    |   dynamically, you have to run config:cache every time to update cache.
    |
    */
    'load_at_startup' => false,
];
