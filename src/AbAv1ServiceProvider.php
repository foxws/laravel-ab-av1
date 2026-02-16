<?php

namespace Foxws\AbAv1;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Foxws\AbAv1\Commands\AbAv1Command;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_ab_av1_table')
            ->hasCommand(AbAv1Command::class);
    }
}
