<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Tests;

use Foxws\AbAv1\AbAv1ServiceProvider;
use Foxws\Media\MediaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MediaServiceProvider::class,
            AbAv1ServiceProvider::class,
        ];
    }
}
