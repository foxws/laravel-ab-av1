<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Commands\AbAv1Command;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AbAv1ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-ab-av1')
            ->hasConfigFile('ab-av1')
            ->hasCommand(AbAv1Command::class);
    }

    public function registeringPackage(): void
    {
        // Register the main AbAv1 class as singleton
        $this->app->singleton(AbAv1::class, function () {
            return new AbAv1;
        });

        // Bind to package name for facade access
        $this->app->alias(AbAv1::class, 'ab-av1');
    }
}
