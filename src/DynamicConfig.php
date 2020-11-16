<?php

namespace Painless\DynamicConfig;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
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

    public function revert($key)
    {
        $default_key = $this->get('dynamic_config.default_prefix');
        $this->set($key, $this->config->get($default_key.'.'.$key));
    }

    public function set($key, $value = null)
    {
        $this->config->set($key, $value);
        $this->addToDatabase($key, $value);
        if ($this->config->get('dynamic_config.enable_cache')) {
            $this->writeToCache();
        }
    }

    public function get($key, $default = null)
    {
        return $this->config->get($key, $default);
    }

    public function update()
    {
        $dynamic_configs = $this->getDynamicConfigFileNames();
        $default_prefix = $this->config->get('dynamic_config.default_prefix');

        $original_config_keys = $this->prefixConfigKeys(
            array_filter($original_configs = $this->config->get($default_prefix), function ($key) use ($dynamic_configs) {
                return in_array($key, $dynamic_configs);
            }, ARRAY_FILTER_USE_KEY)
        );

        $dynamic_config_keys = $this->prefixConfigKeys(
            array_filter($all_configs = $this->config->get($dynamic_configs), function ($key) use ($dynamic_configs, $default_prefix) {
                return in_array($key, $dynamic_configs) && $key !== $default_prefix;
            }, ARRAY_FILTER_USE_KEY)
        );

        $dirty = false;

        if ($this->config->get('dynamic_config.delete_absence_config')) {
            $absence_keys = array_diff($dynamic_config_keys, $original_config_keys);
            Arr::forget($all_configs, $absence_keys);
            if (count($absence_keys) > 0) {
                foreach ($absence_keys as $absence_key) {
                    $this->removeFromDatabase($absence_key);
                }
                $dirty = true;
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
            $dirty = true;
        }

        if ($dirty) {
            $this->writeToCacheFile($all_configs);
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

    protected function writeToCacheFile($configs)
    {
        $cache_file = $this->getCacheFilePath();
        $this->files->put(
            $cache_file,
            '<?php return '.var_export($configs, true).';'.PHP_EOL
        );
        try {
            require $cache_file;
        } catch (Throwable $e) {
            unlink($cache_file);
        }
    }

    public function getDynamicConfigFileNames()
    {
        return array_filter($this->config->get('dynamic_config.dynamic_configs'), function ($item) {
            return $item !== 'dynamic_config';
        });
    }

    public function getCacheFilePath()
    {
        return str_replace('config.php', $this->config->get('dynamic_config.cache_file_name').'.php', app()->getCachedConfigPath());
    }
}
