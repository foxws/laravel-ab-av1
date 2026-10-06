<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Command;
use Foxws\AbAv1\AbAv1Result;
use Foxws\Media\Filesystem\Disk;
use Foxws\Media\Filesystem\ExportResult;

it('reads the last tried CRF as the chosen one', function (): void {
    $output = <<<'TXT'
    - crf 32 VMAF 94.21 (38%)
    - crf 30.25 VMAF 95.32 (45%)
    crf 30.25 VMAF 95.32 predicted video stream size 105.69 MiB (43%) taking 9 minutes
    TXT;

    $result = AbAv1Result::fromOutput(AbAv1Command::CrfSearch, $output);

    expect($result->crf)->toBe(30.25)
        ->and($result->vmaf)->toBe(95.32)
        ->and($result->xpsnr)->toBeNull()
        ->and($result->predictedSize)->toBe((int) round(105.69 * 1024 ** 2))
        ->and($result->predictedPercent)->toBe(43.0)
        ->and($result->predictedTime)->toBe(540.0)
        ->and($result->output)->toBe($output)
        ->and($result->path())->toBeNull();
});

it('reads XPSNR searches', function (): void {
    $result = AbAv1Result::fromOutput(AbAv1Command::AutoEncode, 'crf 28 XPSNR 41.5 predicted video stream size 2.5 GB (50%) taking 30 seconds');

    expect($result->xpsnr)->toBe(41.5)
        ->and($result->vmaf)->toBeNull()
        ->and($result->predictedSize)->toBe(2_500_000_000)
        ->and($result->predictedTime)->toBe(30.0);
});

it('reads the score of comparisons', function (AbAv1Command $command, ?float $vmaf, ?float $xpsnr): void {
    $result = AbAv1Result::fromOutput($command, "computing...\n96.214\n");

    expect($result->vmaf)->toBe($vmaf)
        ->and($result->xpsnr)->toBe($xpsnr)
        ->and($result->crf)->toBeNull();
})->with([
    'vmaf' => [AbAv1Command::Vmaf, 96.214, null],
    'xpsnr' => [AbAv1Command::Xpsnr, null, 96.214],
]);

it('leaves what ab-av1 did not report empty', function (): void {
    $result = AbAv1Result::fromOutput(AbAv1Command::Encode, 'encoding done');

    expect($result->crf)->toBeNull()
        ->and($result->vmaf)->toBeNull()
        ->and($result->predictedSize)->toBeNull()
        ->and($result->predictedPercent)->toBeNull()
        ->and($result->predictedTime)->toBeNull();
});

it('returns the saved path of encodes', function (): void {
    $export = new ExportResult(Disk::make('local'), ['av1/clip.mp4']);

    expect(AbAv1Result::fromOutput(AbAv1Command::AutoEncode, '', $export)->path())->toBe('av1/clip.mp4');
});
