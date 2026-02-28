<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Commands\AbAv1Command;
use Foxws\AbAv1\Filesystem\TemporaryDirectories;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Facades\Config;
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

    public function packageRegistered(): void
    {
        $this->app->singleton('laravel-ab-av1-logger', function () {
            $logChannel = Config::get('ab-av1.log_channel');

            if ($logChannel === false) {
                return null;
            }

            return app('log')->channel($logChannel ?: Config::string('logging.default', 'stack'));
        });

        // Register configuration singleton (must be after logger)
        $this->app->singleton('laravel-ab-av1-configuration', function () {
            return Config::get('ab-av1', []);
        });

        // Register TemporaryDirectories singleton
        $this->app->singleton(TemporaryDirectories::class, function () {
            return new TemporaryDirectories(
                Config::string('ab-av1.temporary_files_root', storage_path('app/ab-av1/temp')),
                Config::string('ab-av1.cache_files_root') ?: null,
            );
        });

        // Register the Encoder as scoped so each request/job gets a fresh instance
        $this->app->scoped(Encoder::class, function ($app) {
            $logger = $app->make('laravel-ab-av1-logger');
            $config = $app->make('laravel-ab-av1-configuration');
            $tempDirs = $app->make(TemporaryDirectories::class);

            return Encoder::create(
                logger: $logger,
                temporaryDirectories: $tempDirs,
                timeout: $config['timeout'] ?? 14400,
                config: $config
            );
        });

        // Register the main class to use with the facade
        $this->app->singleton('ab-av1', function () {
            return new AbAv1(
                Config::string('filesystems.default'),
                null,
                fn () => app(Encoder::class)
            );
        });

        // Register alias for facade access
        $this->app->alias('ab-av1', AbAv1::class);
    }
}
