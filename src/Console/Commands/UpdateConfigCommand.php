<?php


namespace Painless\DynamicConfig\Console\Commands;


use Illuminate\Console\Command;

class UpdateConfigCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dynamic configuration';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:clear');

        $this->laravel['dynamic_config']->update();

        $this->info('Configuration updated!');
    }
}
