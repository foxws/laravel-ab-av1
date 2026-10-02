<?php

namespace Foxws\AbAv1\Tests\Unit;

use Foxws\AbAv1\Filesystem\TemporaryDirectories;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Facades\Process;

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
    expect($args)->toHaveKey('enc-input');
    expect($args['enc-input'])->toBeArray();
    expect($args['enc-input'])->toContain('hwaccel=vaapi');
    expect($args['enc-input'])->toContain('hwaccel_output_format=vaapi');
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

it('applies config values set in .env, which arrive as strings', function () {
    $arguments = Encoder::create(config: [
        'preset' => '4',
        'min_vmaf' => '93.5',
        'max_encoded_percent' => '250',
        'vframes' => '120',
        'samples' => '8',
    ])->getBuilder()->getArguments();

    expect($arguments)->toMatchArray([
        'preset' => 4,
        'min-vmaf' => 93.5,
        'max-encoded-percent' => 250,
        'vframes' => 120,
        'samples' => 8,
    ]);
});

it('keeps a named preset from .env as it is', function () {
    expect(Encoder::create(config: ['preset' => 'medium'])->getBuilder()->getArguments()['preset'])->toBe('medium');
});

/**
 * Runs one encoder command with every process faked, and returns the ab-av1
 * command line it ran (the `which` checks for the binaries are skipped).
 */
function runAbAv1(string $method, ?string $output = null): string
{
    $commands = [];

    Process::fake(function ($process) use (&$commands) {
        $commands[] = $process->command;

        return Process::result();
    });

    $root = sys_get_temp_dir().'/ab-av1-test-'.bin2hex(random_bytes(4));

    $encoder = Encoder::create(
        temporaryDirectories: new TemporaryDirectories("{$root}/temp", "{$root}/cache"),
        config: ['preset' => 6, 'min_vmaf' => 95],
    )->withInput(tempnam(sys_get_temp_dir(), 'ab-av1'))->withCRF(30);

    if ($output) {
        $encoder->withOutput($output);
    }

    $encoder->$method();

    return collect($commands)->first(fn (string $command) => ! str_starts_with($command, 'which'));
}

it('runs the ab-av1 subcommand for each method', function (string $method, string $subcommand) {
    expect(runAbAv1($method, sys_get_temp_dir().'/out.mp4'))->toStartWith("ab-av1 {$subcommand} ");
})->with([
    ['autoEncode', 'auto-encode'],
    ['crfSearch', 'crf-search'],
    ['sampleEncode', 'sample-encode'],
    ['encode', 'encode'],
]);

it('keeps ab-av1 sample files in a temporary directory', function () {
    expect(runAbAv1('crfSearch'))->toMatch('/--temp-dir \S+\/temp\//');
});

it('passes no temp dir to a full encode, which takes no samples', function () {
    expect(runAbAv1('encode', sys_get_temp_dir().'/out.mp4'))->not->toContain('--temp-dir');
});

it('runs a command without an output path', function () {
    expect(runAbAv1('crfSearch'))->toStartWith('ab-av1 crf-search ');
});
