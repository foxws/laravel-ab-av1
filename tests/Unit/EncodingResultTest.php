<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Support\EncodingResult;

it('can parse vmaf score from output', function () {
    $output = <<<'OUTPUT'
    Processing video...
    VMAF score: 95.5
    CRF: 25
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getVMAFScore())->toBe(95.5);
});

it('can parse crf value from output', function () {
    $output = <<<'OUTPUT'
    Processing...
    crf: 32
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getCRFUsed())->toBe(32);
});

it('can parse estimated size from output', function () {
    $output = <<<'OUTPUT'
    Encoding complete
    Estimated encode size: 150 MB
    OUTPUT;

    $result = new EncodingResult('/path/to/video.mp4', $output);

    expect($result->getEstimatedSize())->toBe(150 * 1024 * 1024);
});

it('can parse estimated time from output', function () {
    $output = <<<'OUTPUT'
    Results:
    Estimated encode time: 2h 30m 45s
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
