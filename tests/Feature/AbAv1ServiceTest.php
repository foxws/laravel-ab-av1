<?php

namespace Foxws\AbAv1\Tests\Feature;

use Foxws\AbAv1\AbAv1;
use Foxws\AbAv1\Support\Encoder;

it('can resolve ab-av1 from container', function () {
    $abAv1 = app(AbAv1::class);

    expect($abAv1)->toBeInstanceOf(AbAv1::class);
});

it('facade can create encoder', function () {
    $encoder = AbAv1::encode();

    expect($encoder)->toBeInstanceOf(Encoder::class);
});

it('can create encoder from service instance', function () {
    $abAv1 = app(AbAv1::class);
    $encoder = $abAv1->encoder();

    expect($encoder)->toBeInstanceOf(Encoder::class);
});

it('can apply default configuration', function () {
    config()->set('ab-av1.preset', 'medium');
    config()->set('ab-av1.min_vmaf', 95);

    $abAv1 = app(AbAv1::class);
    $encoder = $abAv1->withDefaults();

    $args = $encoder->getBuilder()->getArguments();

    expect($args['preset'])->toBe('medium');
    expect($args['min-vmaf'])->toBe(95.0);
});

it('applies default encoders from config', function () {
    config()->set('ab-av1.encoders', ['av1_svtenc', 'av1_vaapi']);

    $abAv1 = app(AbAv1::class);
    $encoder = $abAv1->withDefaults();

    $args = $encoder->getBuilder()->getArguments();

    expect($args['encoder'])->toBe('av1_svtenc,av1_vaapi');
});

it('applies default ffmpeg options from config', function () {
    config()->set('ab-av1.ffmpeg_input_options', [
        'hwaccel' => 'vaapi',
    ]);

    $abAv1 = app(AbAv1::class);
    $encoder = $abAv1->withDefaults();

    $args = $encoder->getBuilder()->getArguments();

    expect($args)->toHaveKey('enc-input-hwaccel');
});

it('respects configured timeout', function () {
    config()->set('ab-av1.timeout', 7200);

    $abAv1 = app(AbAv1::class);
    $encoder = $abAv1->encoder();

    expect($encoder->getTimeout())->toBe(7200);
});
