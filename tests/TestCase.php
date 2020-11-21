<?php

namespace Painless\DynamicConfig\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Painless\DynamicConfig\DynamicConfigServiceProvider;
use Painless\DynamicConfig\Facades\DynamicConfig;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            DynamicConfigServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('vendor:publish', ['--provider' => DynamicConfigServiceProvider::class])->run();
        $this->app = $this->createApplication();
        $this->artisan('migrate')->run();
        (new DynamicConfigServiceProvider($this->app))->bootDynamicConfigurations();
        $this->beforeApplicationDestroyed(function () {
            $this->app['files']->delete(
                $this->app['dynamic_config']->getCacheFilePath()
            );
            $this->app['files']->delete(
                $this->app->getCachedConfigPath()
            );
            $this->app['files']->delete(
                config_path('dynamic_config.php')
            );
            $this->app['files']->delete(
                database_path('migrations/2020_11_06_025125_create_dynamic_configs_table.php')
            );
        });
    }

    protected function getPackageAliases($app)
    {
        return [
            'DynamicConfig' => DynamicConfig::class
        ];
    }
}
