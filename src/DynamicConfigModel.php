<?php

namespace Painless\DynamicConfig;

use Illuminate\Database\Eloquent\Model;

class DynamicConfigModel extends Model
{
    protected $table = 'dynamic_configs';

    public $timestamps = false;

}
