<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Command;
use Foxws\AbAv1\Exceptions\AbAv1Exception;

it('names the command and how to fix it', function (): void {
    expect(AbAv1Exception::crfRequired(AbAv1Command::Encode)->getMessage())->toContain('ab-av1 encode', 'withCRF()')
        ->and(AbAv1Exception::comparisonNeedsTwoFiles(AbAv1Command::Vmaf)->getMessage())->toContain('ab-av1 vmaf', "open('original.mp4', 'encoded.mp4')")
        ->and(AbAv1Exception::missingOutput('av1/clip.mp4')->getMessage())->toContain('av1/clip.mp4')
        ->and(new AbAv1Exception)->toBeInstanceOf(RuntimeException::class);
});
