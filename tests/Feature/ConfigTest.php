<?php

namespace Painless\DynamicConfig\Tests\Feature;

use Painless\DynamicConfig\Tests\TestCase;

class ConfigTest extends TestCase
{

    public function testDynamicDataPresistencyTest()
    {
        \DynamicConfig::set('mail.default', 'ses');
        $this->assertSame(config('mail.default'), 'ses');
    }
}
