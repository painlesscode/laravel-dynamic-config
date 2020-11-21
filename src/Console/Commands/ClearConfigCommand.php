<?php

namespace Painless\DynamicConfig\Console\Commands;

use Illuminate\Foundation\Console\ConfigClearCommand;

class ClearConfigCommand extends ConfigClearCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->files->delete($this->laravel->getCachedConfigPath());
        if(!empty($dynamic_cache_file = $this->laravel['dynamic_config']->getCacheFilePath())){
            $this->files->delete($dynamic_cache_file);
        }
        $this->info('Configuration cache cleared!');
    }
}
