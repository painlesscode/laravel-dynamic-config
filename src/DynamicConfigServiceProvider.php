<?php

namespace Painless\DynamicConfig;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Painless\DynamicConfig\Console\Commands\CacheConfigCommand;
use Painless\DynamicConfig\Console\Commands\ClearConfigCommand;
use Painless\DynamicConfig\Console\Commands\UpdateConfigCommand;
use Painless\DynamicConfig\Exceptions\DynamicConfigTableNotFound;

class DynamicConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('dynamic_config', function ($app) {
            return new DynamicConfig($app['config'], $app['files']);
        });

        $this->app->extend('command.config.clear', function (){
            return new ClearConfigCommand($this->app['files']);
        });

        $this->app->extend('command.config.cache', function (){
            return new CacheConfigCommand($this->app['files']);
        });

        $this->app->bind('command.config.update', UpdateConfigCommand::class);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dynamic_config.php' => config_path('dynamic_config.php'),
            ], 'dynamic-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'dynamic-migrations');

            $this->commands([
                UpdateConfigCommand::class,
            ]);
        }

        $this->bootDynamicConfigurations();
    }

    public function bootDynamicConfigurations()
    {
        if ($this->app['config']['dynamic_config.dynamic_configs'] !== null) {
            if(! ($this->app['config']['dynamic_config.loaded_from_cache'] ?? false)) {
                if($this->app['config']['dynamic_config.load_at_startup']) {
                    try {
                        if($this->app['cache']->has('dynamic_config_updating')){
                            $this->app['cache']->put('dynamic_config_updating', true);
                            $this->app['dynamic_config']->update();
                            $this->app['cache']->forget('dynamic_config_updating');
                        }
                    } catch (QueryException $ex) {
                        if (! ($this->app->runningInConsole() || preg_match('/\/?_ignition/', Request::server('REQUEST_URI')))) {
                            throw new DynamicConfigTableNotFound(null, [], $ex);
                        }
                    }
                }
                $this->setDefaultDynamicConfigs();
                if ($this->app['config']['dynamic_config.enable_cache']) {
                    if (! $this->loadConfigFromCache()) {
                        $this->setFromDatabaseAndWriteToCacheFile();
                    }
                } else {
                    $this->setConfigsFromDatabase();
                }
            }
        }
    }

    protected function setDefaultDynamicConfigs()
    {
        $defaults = [];
        $default_key = $this->app['config']['dynamic_config.default_prefix'];
        if (count($this->app['config']->all()) > 0) {
            foreach ($this->getAllowedDynamicKyes() as $config_key) {
                $defaults[$config_key] = $this->app['config'][$config_key];
            }
            $this->app['config'][$default_key] = $defaults;
        }
    }

    protected function getAllowedDynamicKyes()
    {
        return $this->app['dynamic_config']->getDynamicConfigFileNames();
    }

    protected function loadConfigFromCache()
    {
        if ($this->app['files']->exists($cache_file = $this->app['dynamic_config']->getCacheFilePath())) {
            $dynamic_configs = require $cache_file;
            foreach ($this->getAllowedDynamicKyes() as $config_key) {
                $this->app['config'][$config_key] = $dynamic_configs[$config_key];
            }
            return true;
        }
        return false;
    }

    protected function setFromDatabaseAndWriteToCacheFile()
    {
        $configs = $this->setConfigsFromDatabase();

        if (empty($configs)) {
            return;
        }

        $this->app['dynamic_config']->writeToCacheFile($configs);
    }

    protected function setConfigsFromDatabase()
    {
        try {
            $database_configs = DynamicConfigModel::whereIn('key', $this->getAllowedDynamicKyes())->get();
        } catch (QueryException $ex) {
            if ($this->app->runningInConsole() || preg_match('/\/?_ignition/', Request::server('REQUEST_URI'))) {
                return [];
            }
            throw new DynamicConfigTableNotFound(null, [], $ex);
        }

        if ($database_configs->count() === 0) {
            $this->storeConfigToDatabase();
            $database_configs = DynamicConfigModel::whereIn('key', $this->getAllowedDynamicKyes())->get();
        }

        $configs = [];

        foreach ($database_configs as $config) {
            if (in_array($config->key, $this->getAllowedDynamicKyes())) {
                $configs[$config->key] = json_decode($config->values, true);
            }
        }
        $this->app['config']->set($configs);

        return $configs;
    }

    private function storeConfigToDatabase()
    {
        $insert_data = [];

        foreach ($this->getAllowedDynamicKyes() as $key => $value) {
            $insert_data[] = [
                'key' => $value,
                'values' => json_encode($this->app['config'][$value]),
            ];
        }

        if (! empty($insert_data)) {
            DynamicConfigModel::insert($insert_data);
        }

        return collect($insert_data);
    }

}
