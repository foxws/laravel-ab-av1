<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\Media\Executables\Executables;
use Foxws\Media\Opener;
use Illuminate\Support\ServiceProvider;

class AbAv1ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ab-av1.php', 'ab-av1');

        Opener::macro('abAv1', function (): AbAv1Builder {
            /** @var Opener $this */
            return app(AbAv1Builder::class, ['opener' => $this]);
        });
    }

    public function boot(): void
    {
        $this->app->make(Executables::class)->register(AbAv1Executable::AbAv1);

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/ab-av1.php' => config_path('ab-av1.php'),
        ], ['ab-av1', 'ab-av1-config']);
    }
}
