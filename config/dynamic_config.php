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
];
