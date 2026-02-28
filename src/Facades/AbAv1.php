<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Foxws\AbAv1\MediaOpener fromDisk(string $disk)
 * @method static \Foxws\AbAv1\MediaOpener open(string $path)
 * @method static \Foxws\AbAv1\MediaOpener cleanupTemporaryFiles()
 * @method static \Foxws\AbAv1\Filesystem\Exporter export()
 *
 * @see \Foxws\AbAv1\AbAv1
 */
class AbAv1 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ab-av1';
    }
}
