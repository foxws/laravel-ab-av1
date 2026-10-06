<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Executable;
use Foxws\AbAv1\Testing\FakeAbAv1;
use Foxws\Media\Facades\Media;
use Foxws\Media\Process\Runner;

beforeEach(function (): void {
    $this->fake = FakeAbAv1::respond(Media::fake(), crf: 31.5, score: 96.0, percent: 40);
    $this->directory = sys_get_temp_dir().'/laravel-ab-av1-fake-'.bin2hex(random_bytes(4));
});

it('writes the output of encodes and reports the CRF and score', function (): void {
    $output = "{$this->directory}/av1/clip.mp4";

    $result = app(Runner::class)->run(AbAv1Executable::AbAv1, ['auto-encode', '--input', 'clip.mp4', '--output', $output]);

    expect($output)->toBeFile()
        ->and($result->output)->toBe("crf 31.5 VMAF 96 predicted video stream size 105.69 MiB (40%) taking 9 minutes\n");
});

it('reports the CRF an encode was given', function (): void {
    $result = app(Runner::class)->run(AbAv1Executable::AbAv1, ['sample-encode', '--input', 'clip.mp4', '--crf', '28.25']);

    expect($result->output)->toStartWith('crf 28.25 VMAF 96 ');
});

it('reports XPSNR for XPSNR searches', function (): void {
    $result = app(Runner::class)->run(AbAv1Executable::AbAv1, ['crf-search', '--input', 'clip.mp4', '--min-xpsnr', '40']);

    expect($result->output)->toContain('XPSNR 96');
});

it('prints only the score for comparisons', function (): void {
    $result = app(Runner::class)->run(AbAv1Executable::AbAv1, ['vmaf', '--reference', 'a.mp4', '--distorted', 'b.mp4']);

    expect($result->output)->toBe("96\n");

    $this->fake->assertRan(AbAv1Executable::AbAv1, fn (array $arguments): bool => $arguments[0] === 'vmaf');
});
