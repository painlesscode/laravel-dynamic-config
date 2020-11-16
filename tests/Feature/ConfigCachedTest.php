<?php

namespace Painless\DynamicConfig\Tests\Feature;

use Painless\DynamicConfig\DynamicConfigModel;
use Painless\DynamicConfig\DynamicConfigServiceProvider;
use Painless\DynamicConfig\Tests\TestCase;

class ConfigCachedTest extends TestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);
        $app['config']['dynamic_config.enable_cache'] = true;
        $app['config']['mail.default'] = 'array';
    }

    public function testCacheEnabledTest()
    {
        $this->assertSame(config('dynamic_config.enable_cache'), true);
    }

    public function testcacheFileGeneratedTest()
    {
        $this->assertTrue($this->app['files']->exists(
            str_replace('config.php', $this->app['config']['dynamic_config.cache_file_name'].'.php', $this->app->getCachedConfigPath())
        ));
    }

    public function testLoadFromCacheTest()
    {
        $this->assertNotEmpty(DynamicConfigModel::all()->toArray());
        $first = DynamicConfigModel::first();
        $values = json_decode($first->values, true);
        $values['default'] = 'ses';
        $first->values = json_encode($values);
        $first->save();
        (new DynamicConfigServiceProvider($this->app))->bootDynamicConfigurations();
        $this->assertSame($this->app['config']->get('mail.default'), 'array');
    }
}
