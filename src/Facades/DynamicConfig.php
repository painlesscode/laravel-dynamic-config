<?php


namespace Painless\DynamicConfig\Facades;


use Illuminate\Support\Facades\Facade;

class DynamicConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dynamic_config';
    }
}
