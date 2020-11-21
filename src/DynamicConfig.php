<?php

namespace Painless\DynamicConfig;

use ErrorException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use LogicException;
use Throwable;

class DynamicConfig
{
    protected $config;
    protected $files;

    public function __construct(Repository $config, Filesystem $files)
    {
        $this->config = $config;
        $this->files = $files;
    }

    public function get($key, $default = null)
    {
        return $this->config->get($key, $default);
    }

    public function set($key, $value = null)
    {
        $this->config->set($key, $value);
        if(explode('.', $key)[0] !== 'dynamic_config'){
            $this->addToDatabase($key, $value);
            if ($this->config->get('dynamic_config.enable_cache')) {
                $this->writeToCache();
            }
            if($this->config->get('dynamic_config.load_at_startup')){
                $this->writeToStartUpCacheFile($this->config->all());
            }
        }
    }

    public function revert($key, $persist = true)
    {
        $default_key = $this->get('dynamic_config.default_prefix');
        if($persist) {
            $this->set($key, $this->config->get($default_key.'.'.$key));
        }
        else {
            $this->config->set($key, $this->config->get($default_key.'.'.$key));
        }
    }

    public function update()
    {
        $dynamic_configs = $this->getDynamicConfigFileNames();
        $default_prefix = $this->config->get('dynamic_config.default_prefix');

        $original_config_keys = $this->prefixConfigKeys(
            array_filter($original_configs = $this->config->get($default_prefix) ?? [], function ($key) use ($dynamic_configs) {
                return in_array($key, $dynamic_configs);
            }, ARRAY_FILTER_USE_KEY)
        );

        $dynamic_config_keys = $this->prefixConfigKeys(
            array_filter($all_configs = $this->config->get($dynamic_configs), function ($key) use ($dynamic_configs, $default_prefix) {
                return in_array($key, $dynamic_configs) && $key !== $default_prefix;
            }, ARRAY_FILTER_USE_KEY)
        );

        DynamicConfigModel::whereNotIn('key', $dynamic_configs)->delete();


        if ($this->config->get('dynamic_config.delete_absence_config')) {
            $absence_keys = array_diff($dynamic_config_keys, $original_config_keys);
            Arr::forget($all_configs, $absence_keys);
            if (count($absence_keys) > 0) {
                foreach ($absence_keys as $absence_key) {
                    $this->config->offsetUnset($absence_key);
                    $this->removeFromDatabase($absence_key);
                }
            }
        }

        foreach (array_diff($original_config_keys, $dynamic_config_keys) as $new_key) {
            Arr::set(
                $all_configs,
                $new_key,
                Arr::get($original_configs, $new_key)
            );
            $this->addToDatabase(
                $new_key,
                Arr::get($original_configs, $new_key)
            );
        }

        $this->writeToCacheFile($all_configs);
        if ($this->config->get('dynamic_config.enable_cache')) {
            $this->writeToCacheFile($all_configs);
        }
        if($this->config->get('dynamic_config.load_at_startup')){
            $this->writeToStartUpCacheFile($this->getFreshConfiguration());
        }
    }

    protected function getFreshConfiguration(){
        try {
            $app = require app()->bootstrapPath().'/app.php';
            $app->useStoragePath(app()->storagePath());
            $app->make(ConsoleKernelContract::class)->bootstrap();
            return $app['config']->all();
        } catch (ErrorException $ex){
            return $this->config->all();
        }
    }

    protected function prefixConfigKeys($array, $prefix = null)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::prefixConfigKeys($value, $prefix.$key.'.'));
            } else {
                $result[] = $prefix.$key;
            }
        }

        return $result;
    }

    protected function removeFromDatabase($key)
    {
        $segments = explode('.', $key);
        $count = count($segments);
        if ($count > 0) {
            $model = DynamicConfigModel::where('key', $segments[0])->first();
            $config = json_decode(
                $model->values ?? '{}',
                true
            );
            if ($count > 1) {
                $key = implode('.', array_slice($segments, 1));
                Arr::forget($config, $key);
                $model->values = json_encode($config);
                $model->save();
            } else {
                $model->delete();
            }
        }
    }

    protected function addToDatabase($key, $value)
    {
        $segments = explode('.', $key);
        $count = count($segments);
        if ($count > 0) {
            $model = DynamicConfigModel::where('key', $segments[0])->first();
            if (empty($model)) {
                $model = new DynamicConfigModel();
                $model->key = $segments[0];
            }
            if ($count > 1) {
                $config = json_decode($model->values, true);
                $key = implode('.', array_slice($segments, 1));
                Arr::set($config, $key, $value);
                $model->values = json_encode($config);
            } else {
                $model->key = $segments[0];
                $model->values = is_array($value) ? json_encode($value) : $value;
            }

            $model->save();
        }
    }

    protected function writeToCache()
    {
        $allowed_dynamic = $this->getDynamicConfigFileNames();
        $configs = [];
        foreach ($allowed_dynamic as $config_key) {
            $configs[$config_key] = $this->config->get($config_key);
        }
        $this->writeToCacheFile($configs);
    }

    public function writeToCacheFile($configs, $file_name = null)
    {
        $cache_file = $file_name ?? $this->getCacheFilePath();
        $this->files->put(
            $cache_file,
            '<?php return '.var_export($configs, true).';'.PHP_EOL
        );
        try {
            require $cache_file;
            return true;
        } catch (Throwable $e) {
            $this->files->delete($cache_file);
            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }
    }

    protected function writeToStartUpCacheFile($configs){
        $configs['dynamic_config']['loaded_from_cache'] = true;
        return $this->writeToCacheFile($configs, app()->getCachedConfigPath());
    }

    public function getDynamicConfigFileNames()
    {
        return array_filter($this->config->get('dynamic_config.dynamic_configs') ?? [], static function ($item) {
            return $item !== 'dynamic_config';
        });
    }

    public function getCacheFilePath()
    {
        return str_replace('config.php', $this->config->get('dynamic_config.cache_file_name').'.php', app()->getCachedConfigPath());
    }
}
