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
        // Register logger singleton
        $this->app->singleton('laravel-ab-av1-logger', function () {
            $logChannel = Config::get('ab-av1.log_channel');

            if ($logChannel === false) {
                return null;
            }

            return app('log')->channel($logChannel ?: Config::get('logging.default'));
        });

        // Register configuration singleton
        $this->app->singleton('laravel-ab-av1-configuration', function () {
            $baseConfig = [
                'timeout' => Config::integer('ab-av1.timeout', 3600),
                'preset' => Config::get('ab-av1.preset', 8),
                'min_vmaf' => Config::get('ab-av1.min_vmaf', 95),
                'max_encoded_percent' => Config::get('ab-av1.max_encoded_percent', 200),
                'encoders' => Config::get('ab-av1.encoders', []),
                'ffmpeg_input_options' => Config::get('ab-av1.ffmpeg_input_options', []),
            ];

            if ($configuredTemporaryRoot = Config::string('ab-av1.temporary_files_root', '')) {
                $baseConfig['temporary_directory'] = $configuredTemporaryRoot;
            }

            return $baseConfig;
        });

        // Register TemporaryDirectories singleton
        $this->app->singleton(TemporaryDirectories::class, function () {
            return new TemporaryDirectories(
                Config::string('ab-av1.temporary_files_root', storage_path('app/ab-av1/temp')),
                Config::string('ab-av1.cache_files_root', '') ?: null,
            );
        });

        // Register the Encoder
        $this->app->singleton(Encoder::class, function ($app) {
            $logger = $app->make('laravel-ab-av1-logger');
            $config = $app->make('laravel-ab-av1-configuration');
            $tempDirs = $app->make(TemporaryDirectories::class);

            $encoder = Encoder::create($logger, $tempDirs, $config['timeout']);

            // Apply configuration defaults
            if (isset($config['max_encoded_percent'])) {
                $encoder->withMaxEncodedPercent($config['max_encoded_percent']);
            }

            if (! empty($config['ffmpeg_input_options'])) {
                $encoder->withFFmpegOptions($config['ffmpeg_input_options']);
            }

            return $encoder;
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
