<?php


namespace Painless\DynamicConfig\Tests\Feature;


use Painless\DynamicConfig\DynamicConfigModel;
use Painless\DynamicConfig\DynamicConfigServiceProvider;
use Painless\DynamicConfig\Tests\TestCase;

class ConfigStartupTest extends TestCase
{

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);
    }

    public function testExceptionTest(){
        $this->app['config']['dynamic_config.load_at_startup'] = true;
        $this->app['dynamic_config']->update();
        $cache_file_content = $this->app['files']->get(
            $this->app->getCachedConfigPath()
        );
        $this->app['config']['dynamic_config.loaded_from_cache'] = true;
        $this->assertSame('<?php return '.var_export($this->app['config']->all(), true).';'.PHP_EOL, $cache_file_content);
    }
}
