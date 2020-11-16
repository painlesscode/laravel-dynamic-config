# Laravel Dynamic Config

![PHP Composer test](https://github.com/painlesscode/laravel-dynamic-config/workflows/PHP%20Composer/badge.svg)

## Introduction
This Package allow users to have their configuration stored in database, makes it easy to customize.
Support cache for faster access.

## Installation
You can install the package via composer:
``` bash
composer require painlesscode/laravel-dynamic-config
```
publish the config with:

``` bash
php artisan vendor:publish --provider="Painless\DynamicConfig\DynamicConfigServiceProvider"
```
 
## Usage

You just need to decide which config file(s) you want them to be dynamically editable by appending file name to `dynamic_configs` array :
```php
# /config/dynamic_config.php 
return [
    'dynamic_configs' => [
        'mail',
    ],
];
```

> * `mail` is added at `dynamic_config` array for testing purpose. You are free to remove it, if you don't need it.
> * The default values will be taken from the actual config file.
> * Adding `dynamic_config` to the `dynamic_configs` array have no effect.
> * You can enable cache for faster access. To enable cache dynamic configuration. just edit `enable_cache` key of `dynamic_config.php` file to `true`.
> * Cache file will be stored at `bootstrap/cache/dynamic_config.php` file. You can change the cache file name by editing `cache_file_name` key of of `dynamic_config.php` file. 


#### Getting Dynamic Config Value

```php
echo config('mail.default'); // Will return the value of dynamic mail.default (if mail is already added to dynamic_configs array);
```

#### Getting Original Config Value

```php
echo config('defailt.mail.default'); // Will return the value of original configuration (if default_prefix is set to 'default');
```

#### Setting Dynamic Config Value

```php
config('mail.default', 'array'); // It is like default laravel config set. it will be set but persists in only current request.
// to set value permanently
use Painless\DynamicConfig\Facades\DynamicConfig; // or you can use DynamicConfig Alias

DynamicConfig::set('mail.default', 'ses'); // It will save the value and persist it in database and cache (if enabled)
```

#### Revert config value
to revert a config value to its original state:
```php
use Painless\DynamicConfig\Facades\DynamicConfig; // or you can use DynamicConfig Alias

DynamicConfig::revert('mail.default', 'ses'); // It will revert the config value to its original state and persist it. 
```
