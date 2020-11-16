<?php

namespace Painless\DynamicConfig;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Painless\DynamicConfig\Exceptions\DynamicConfigTableNotFound;
use Throwable;

class DynamicConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('dynamic_config', function ($app) {
            return new DynamicConfig($app['config'], $app['files']);
        });
    }

    public function boot()
    {
        $this->bootDynamicConfigurations();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dynamic_config.php' => config_path('dynamic_config.php'),
            ], 'dynamic-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'dynamic-migrations');
        }
    }

    public function bootDynamicConfigurations()
    {
        if ($this->app['config']['dynamic_config.dynamic_configs'] !== null) {
            $this->setDynamicConfigs();
            if ($this->app['config']['dynamic_config']['enable_cache']) {
                if (! $this->loadConfigFromCache()) {
                    $this->setFromDatabaseAndWriteToCacheFile();
                }
            } else {
                $this->setConfigsFromDatabase();
            }
        }
    }

    protected function setDynamicConfigs()
    {
        $defaults = [];
        if (count($this->app['config']->all()) > 0) {
            foreach ($this->getAllowedDynamicKyes() as $config_key) {
                $defaults[$config_key] = $this->app['config'][$config_key];
            }
            $this->app['config']['default'] = $defaults;
        }
    }

    protected function getAllowedDynamicKyes()
    {
        return $this->app['dynamic_config']->getDynamicConfigFileNames();
    }

    protected function loadConfigFromCache()
    {
        if (file_exists($cache_file = $this->app['dynamic_config']->getCacheFilePath())) {
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
        $cache_file = $this->app['dynamic_config']->getCacheFilePath();
        $configs = $this->setConfigsFromDatabase();

        if (empty($configs)) {
            return;
        }

        $this->app['files']->put(
            $cache_file, '<?php return '.var_export($configs, true).';'.PHP_EOL
        );
        try {
            require $cache_file;
        } catch (Throwable $e) {
            $this->app['files']->delete($cache_file);
        }
    }

    protected function setConfigsFromDatabase()
    {
        try {
            $database_configs = DynamicConfigModel::all();
        } catch (QueryException $ex) {
            if ($this->app->runningInConsole() || preg_match('/\/?_ignition/', Request::server('REQUEST_URI'))) {
                return [];
            }
            throw new DynamicConfigTableNotFound(null, [], $ex);
        }

        if ($database_configs->count() === 0) {
            $this->storeConfigToDatabase();
            $database_configs = DynamicConfigModel::all();
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
