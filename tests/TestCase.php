<?php

declare(strict_types=1);

namespace Stayblox\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Stayblox\Core\StaybloxServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [StaybloxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
