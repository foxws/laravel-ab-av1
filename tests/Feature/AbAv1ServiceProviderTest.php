<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Builder;
use Foxws\AbAv1\AbAv1Executable;
use Foxws\AbAv1\AbAv1ServiceProvider;
use Foxws\Media\Executables\Executables;
use Foxws\Media\Facades\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

it('adds abAv1() to opened media', function (): void {
    Storage::fake('media');
    Media::fake();

    $builder = Media::fromDisk('media')->open('clip.mp4')->abAv1();

    expect($builder)->toBeInstanceOf(AbAv1Builder::class)
        ->and($builder->media()->paths())->toBe(['clip.mp4'])
        ->and($builder->disk()->name())->toBe('media');
});

it('registers ab-av1 with media:info and about', function (): void {
    expect(app(Executables::class)->all())->toContain(AbAv1Executable::AbAv1);
});

it('merges and publishes the config', function (): void {
    expect(config('ab-av1.binary'))->toBe('ab-av1')
        ->and(config('ab-av1.timeout'))->toBe(14400)
        ->and(array_values(ServiceProvider::pathsToPublish(AbAv1ServiceProvider::class, 'ab-av1-config')))
        ->toBe([config_path('ab-av1.php')]);
});
