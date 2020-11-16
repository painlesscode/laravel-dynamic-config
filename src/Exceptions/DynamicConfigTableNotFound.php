<?php


namespace Painless\DynamicConfig\Exceptions;


use Illuminate\Database\QueryException;
use Throwable;

class DynamicConfigTableNotFound extends QueryException
{

    protected function formatMessage($sql, $bindings, Throwable $previous)
    {
        return 'May be you forgotten to run migration after publishing painlesscode/laravel-dynamic-config package';
    }
}
