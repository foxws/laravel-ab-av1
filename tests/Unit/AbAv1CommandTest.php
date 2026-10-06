<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Command;

it('tells comparisons from encodes', function (AbAv1Command $command, bool $compares, bool $encodesSamples): void {
    expect($command->compares())->toBe($compares)
        ->and($command->encodesSamples())->toBe($encodesSamples);
})->with([
    'auto-encode' => [AbAv1Command::AutoEncode, false, true],
    'crf-search' => [AbAv1Command::CrfSearch, false, true],
    'sample-encode' => [AbAv1Command::SampleEncode, false, true],
    'encode' => [AbAv1Command::Encode, false, false],
    'vmaf' => [AbAv1Command::Vmaf, true, false],
    'xpsnr' => [AbAv1Command::Xpsnr, true, false],
]);
