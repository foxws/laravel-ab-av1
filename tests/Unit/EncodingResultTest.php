<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Support\EncodingResult;

it('can parse vmaf score from output', function () {
    $output = <<<'OUTPUT'
    [2024-01-16T13:40:23Z INFO  ab_av1::command::sample_encode] crf 19 VMAF 95.5 predicted video stream size 186.95 MiB (127%) taking 86 seconds
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getVMAFScore())->toBe(95.5);
});

it('can parse crf value from output', function () {
    $output = <<<'OUTPUT'
    [2024-01-16T13:40:31Z INFO  ab_av1::command::sample_encode] crf 32 VMAF 94.80 predicted video stream size 174.92 MiB (119%) taking 81 seconds
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getCRFUsed())->toBe(32);
});

it('can parse estimated size from output', function () {
    $output = <<<'OUTPUT'
    [2024-01-16T13:40:23Z INFO  ab_av1::command::sample_encode] crf 19 VMAF 95.03 predicted video stream size 150 MiB (127%) taking 86 seconds
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getEstimatedSize())->toBe(150 * 1024 * 1024);
});

it('can parse estimated time from output', function () {
    $output = <<<'OUTPUT'
    [2024-01-16T13:40:23Z INFO  ab_av1::command::sample_encode] crf 19 VMAF 95.03 predicted video stream size 186.95 MiB (127%) taking 9045 seconds
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getEstimatedTime())->toBeGreaterThan(9000);
});

it('can set and get output path', function () {
    $result = new EncodingResult('/path/to/input.mp4', 'output');

    $result->setOutputPath('/path/to/output.mp4');

    expect($result->getOutputPath())->toBe('/path/to/output.mp4');
});

it('can set and get VMAF score', function () {
    $result = new EncodingResult('/path/to/video.mp4', 'output');

    $result->setVMAFScore(85.5);

    expect($result->getVMAFScore())->toBe(85.5);
});

it('can get metadata', function () {
    $result = new EncodingResult('/path/to/video.mp4', 'output', [
        'encoder_used' => 'av1_svtenc',
        'preset' => 'medium',
    ]);

    expect($result->getMetadata('encoder_used'))->toBe('av1_svtenc');
    expect($result->getMetadata('preset'))->toBe('medium');
    expect($result->getMetadata('unknown', 'default'))->toBe('default');
});

it('can convert to array', function () {
    $result = new EncodingResult('/path/to/video.mp4', 'output');
    $result->setOutputPath('/path/to/output.mp4');
    $result->setVMAFScore(95.0);
    $result->setCRFUsed(25);

    $array = $result->toArray();

    expect($array)->toHaveKey('input_path');
    expect($array)->toHaveKey('output_path');
    expect($array)->toHaveKey('vmaf_score');
    expect($array['vmaf_score'])->toBe(95.0);
});
