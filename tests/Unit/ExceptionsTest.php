<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Exceptions\EncodingException;
use Foxws\AbAv1\Exceptions\ExecutableNotFoundException;
use Foxws\AbAv1\Exceptions\InvalidEncodingConfigurationException;
use Foxws\AbAv1\Exceptions\MediaNotFoundException;
use Foxws\AbAv1\Exceptions\RuntimeException;
use Foxws\AbAv1\Support\Encoder;

it('throws executable not found exception for ab-av1', function () {
    $exception = ExecutableNotFoundException::abAv1NotFound();

    expect($exception)->toBeInstanceOf(EncodingException::class);
    expect($exception->getMessage())->toContain('ab-av1');
});

it('throws executable not found exception for ffmpeg', function () {
    $exception = ExecutableNotFoundException::ffmpegNotFound();

    expect($exception)->toBeInstanceOf(EncodingException::class);
    expect($exception->getMessage())->toContain('ffmpeg');
});

it('throws invalid configuration exception for min vmaf', function () {
    $exception = InvalidEncodingConfigurationException::minVMAFRequired();

    expect($exception)->toBeInstanceOf(EncodingException::class);
    expect($exception->getMessage())->toContain('min-vmaf');
});

it('throws invalid configuration exception for preset', function () {
    $exception = InvalidEncodingConfigurationException::presetRequired();

    expect($exception)->toBeInstanceOf(EncodingException::class);
    expect($exception->getMessage())->toContain('preset');
});

it('throws invalid configuration exception for input', function () {
    $exception = InvalidEncodingConfigurationException::inputRequired();

    expect($exception)->toBeInstanceOf(EncodingException::class);
    expect($exception->getMessage())->toContain('Input');
});

it('throws a media not found exception for a missing input file', function () {
    (new Encoder)->withInput('/path/that/does/not/exist.mp4');
})->throws(MediaNotFoundException::class);

it('keeps every package exception catchable as a runtime exception', function (string $exception) {
    expect(is_subclass_of($exception, \RuntimeException::class))->toBeTrue();
})->with([
    EncodingException::class,
    ExecutableNotFoundException::class,
    InvalidEncodingConfigurationException::class,
    MediaNotFoundException::class,
    RuntimeException::class,
]);
