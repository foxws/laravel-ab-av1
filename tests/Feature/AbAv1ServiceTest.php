<?php

namespace Foxws\AbAv1\Tests\Feature;

use Foxws\AbAv1\AbAv1;
use Foxws\AbAv1\MediaOpener;

it('can resolve ab-av1 from container', function () {
    $abAv1 = app(AbAv1::class);

    expect($abAv1)->toBeInstanceOf(AbAv1::class);
});

it('can create media opener', function () {
    $abAv1 = app(AbAv1::class);
    $opener = $abAv1->new();

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('respects configured timeout', function () {
    config()->set('ab-av1.timeout', 7200);

    // Resolve fresh encoder from container
    app()->forgetInstance('laravel-ab-av1-configuration');
    app()->forgetInstance(\Foxws\AbAv1\Support\Encoder::class);

    $abAv1 = app(AbAv1::class);
    $opener = $abAv1->new();

    expect($opener->getTimeout())->toBe(7200);
});
