<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Support\CommandBuilder;

it('can build auto-encode command', function () {
    $command = CommandBuilder::make()
        ->autoEncode()
        ->withInput('/path/to/video.mp4')
        ->withPreset('medium')
        ->withMinVMAF(95)
        ->build();

    expect($command)->toContain('ab-av1 auto-encode');
    expect($command)->toContain('--input');
    expect($command)->toContain('/path/to/video.mp4');
    expect($command)->toContain('--preset');
    expect($command)->toContain('--min-vmaf');
});

it('can build encode command', function () {
    $command = CommandBuilder::make()
        ->encode()
        ->withInput('/path/to/video.mp4')
        ->withCRF(25)
        ->withPreset('slow')
        ->build();

    expect($command)->toContain('ab-av1 encode');
    expect($command)->toContain('--crf');
    expect($command)->toContain('--preset');
});

it('can set encoder', function () {
    $command = CommandBuilder::make()
        ->withEncoder('av1_svtenc')
        ->build();

    expect($command)->toContain('--encoder');
    expect($command)->toContain('av1_svtenc');
});

it('can set multiple encoders', function () {
    $command = CommandBuilder::make()
        ->withEncoders(['av1_svtenc', 'av1_vaapi'])
        ->build();

    expect($command)->toContain('--encoder');
    expect($command)->toContain('av1_svtenc,av1_vaapi');
});

it('validates crf range', function () {
    CommandBuilder::make()->withCRF(64);
})->throws(\InvalidArgumentException::class);

it('validates preset values', function () {
    CommandBuilder::make()->withPreset('invalid-preset');
})->throws(\InvalidArgumentException::class);

it('can enable json output', function () {
    $command = CommandBuilder::make()
        ->jsonOutput()
        ->build();

    expect($command)->toContain('--stdout-format');
    expect($command)->toContain('json');
});

it('can set custom options', function () {
    $command = CommandBuilder::make()
        ->withOption('max-encoded-percent', 150)
        ->build();

    expect($command)->toContain('--max-encoded-percent');
    expect($command)->toContain('150');
});

it('can convert to string', function () {
    $builder = CommandBuilder::make()
        ->autoEncode()
        ->withInput('/path/to/video.mp4')
        ->withPreset('medium')
        ->withMinVMAF(95);

    $command = (string) $builder;

    expect($command)->toContain('ab-av1 auto-encode');
});

it('can set numeric preset (0-8)', function () {
    $command = CommandBuilder::make()
        ->withPreset(4)
        ->build();

    expect($command)->toContain('--preset');
    expect($command)->toContain('4');
});

it('can set string preset', function () {
    $command = CommandBuilder::make()
        ->withPreset('slow')
        ->build();

    expect($command)->toContain('--preset');
    expect($command)->toContain('slow');
});

it('validates numeric preset range', function () {
    CommandBuilder::make()->withPreset(9);
})->throws(\InvalidArgumentException::class);

it('can set encoder args', function () {
    $command = CommandBuilder::make()
        ->crfSearch()
        ->withInput('/path/to/video.mp4')
        ->withEncoder('av1_qsz')
        ->withEncoderArgs('av1_qsz_params=preset=slow:lookahead=1:lookahead_depth=60:extbrc=1')
        ->build();

    expect($command)->toContain('crf-search');
    expect($command)->toContain('--encoder');
    expect($command)->toContain('av1_qsz');
    expect($command)->toContain('--enc');
    expect($command)->toContain('av1_qsz_params=preset=slow:lookahead=1:lookahead_depth=60:extbrc=1');
});
