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
        // Register configuration singleton (must be first)
        $this->app->singleton('laravel-ab-av1-configuration', function () {
            return Config::get('ab-av1', []);
        });

        // Register logger singleton
        $this->app->singleton('laravel-ab-av1-logger', function ($app) {
            $config = $app->make('laravel-ab-av1-configuration');
            $logChannel = $config['log_channel'] ?? null;

            if ($logChannel === false) {
                return null;
            }

            return app('log')->channel($logChannel ?: config('logging.default'));
        });

        // Register TemporaryDirectories singleton
        $this->app->singleton(TemporaryDirectories::class, function ($app) {
            $config = $app->make('laravel-ab-av1-configuration');

            return new TemporaryDirectories(
                $config['temporary_files_root'] ?? storage_path('app/ab-av1/temp'),
                $config['cache_files_root'] ?? null,
            );
        });

        // Register the Encoder
        $this->app->singleton(Encoder::class, function ($app) {
            $logger = $app->make('laravel-ab-av1-logger');
            $config = $app->make('laravel-ab-av1-configuration');
            $tempDirs = $app->make(TemporaryDirectories::class);

            $encoder = Encoder::create($logger, $tempDirs, $config['timeout'] ?? 3600);

            // Apply configuration defaults
            if (filled($config['max_encoded_percent'] ?? null)) {
                $encoder->withMaxEncodedPercent($config['max_encoded_percent']);
            }

            if (filled($config['vframes'] ?? null)) {
                $encoder->withVFrames($config['vframes']);
            }

            if (filled($config['samples'] ?? null)) {
                $encoder->withSamples($config['samples']);
            }

            if (filled($config['encoder'] ?? null)) {
                $encoder->withEncoder($config['encoder']);
            }

            if (filled($config['encoder_args'] ?? null)) {
                $encoder->withEncoderArgs($config['encoder_args']);
            }

            if (filled($config['pix_format'] ?? null)) {
                $encoder->withPixelFormat($config['pix_format']);
            }

            if (filled($config['video_filter'] ?? null)) {
                $encoder->withVideoFilter($config['video_filter']);
            }

            if (filled($config['verbosity'] ?? null)) {
                $encoder->withVerbosity($config['verbosity']);
            }

            if (filled($config['ffmpeg_input_options'] ?? null)) {
                $encoder->withFFmpegOptions($config['ffmpeg_input_options']);
            }

            return $encoder;
        });

        // Register the main class to use with the facade
        $this->app->singleton('ab-av1', function ($app) {
            $config = $app->make('laravel-ab-av1-configuration');

            return new AbAv1(
                $config['default_disk'] ?? config('filesystems.default'),
                null,
                fn () => app(Encoder::class)
            );
        });

        // Register alias for facade access
        $this->app->alias('ab-av1', AbAv1::class);
    }
}
