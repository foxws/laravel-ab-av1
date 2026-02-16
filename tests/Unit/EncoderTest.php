<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Support\Encoder;

it('can create an encoder instance', function () {
    $encoder = Encoder::create();

    expect($encoder)->toBeInstanceOf(Encoder::class);
});

it('can set input file', function () {
    $testFile = __FILE__;

    $encoder = Encoder::create()->withInput($testFile);

    expect($encoder->getBuilder()->getArguments())->toHaveKey('input');
});

it('throws for non-existent input file', function () {
    Encoder::create()->withInput('/non/existent/file.mp4');
})->throws(\RuntimeException::class);

it('can set preset', function () {
    $encoder = Encoder::create()->withPreset('medium');

    expect($encoder->getBuilder()->getArguments()['preset'])->toBe('medium');
});

it('can set crf', function () {
    $encoder = Encoder::create()->withCRF(25);

    expect($encoder->getBuilder()->getArguments()['crf'])->toBe(25);
});

it('can set min vmaf', function () {
    $encoder = Encoder::create()->withMinVMAF(95);

    expect($encoder->getBuilder()->getArguments()['min-vmaf'])->toBe(95.0);
});

it('can set encoder', function () {
    $encoder = Encoder::create()->withEncoder('av1_svtenc');

    expect($encoder->getBuilder()->getArguments()['encoder'])->toBe('av1_svtenc');
});

it('can set multiple encoders', function () {
    $encoder = Encoder::create()->withEncoders(['av1_svtenc', 'av1_vaapi']);

    expect($encoder->getBuilder()->getArguments()['encoder'])->toBe('av1_svtenc,av1_vaapi');
});

it('can set timeout', function () {
    $encoder = Encoder::create()->setTimeout(7200);

    expect($encoder->getTimeout())->toBe(7200);
});

it('can set ffmpeg options', function () {
    $encoder = Encoder::create()->withFFmpegOptions([
        'hwaccel' => 'vaapi',
        'hwaccel_output_format' => 'vaapi',
    ]);

    $args = $encoder->getBuilder()->getArguments();
    expect($args)->toHaveKey('enc-input-hwaccel');
    expect($args)->toHaveKey('enc-input-hwaccel_output_format');
});

it('validates auto-encode configuration - missing input', function () {
    $encoder = Encoder::create()
        ->withPreset('medium')
        ->withMinVMAF(95);

    $encoder->autoEncode();
})->throws(\Exception::class);

it('validates auto-encode configuration - missing preset', function () {
    $testFile = __FILE__;

    $encoder = Encoder::create()
        ->withInput($testFile)
        ->withMinVMAF(95);

    $encoder->autoEncode();
})->throws(\Exception::class);

it('validates auto-encode configuration - missing min vmaf', function () {
    $testFile = __FILE__;

    $encoder = Encoder::create()
        ->withInput($testFile)
        ->withPreset('medium');

    $encoder->autoEncode();
})->throws(\Exception::class);
