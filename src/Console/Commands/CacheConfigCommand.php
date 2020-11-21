<?php


namespace Painless\DynamicConfig\Console\Commands;


use Illuminate\Foundation\Console\ConfigCacheCommand;
use LogicException;
use Throwable;

class CacheConfigCommand extends ConfigCacheCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:clear');

        $this->call('config:update');

        $this->info('Configuration cached successfully!');
    }
}
