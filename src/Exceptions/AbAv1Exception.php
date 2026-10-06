<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Exceptions;

use Foxws\AbAv1\AbAv1Command;
use RuntimeException;

class AbAv1Exception extends RuntimeException
{
    public static function crfRequired(AbAv1Command $command): self
    {
        return new self("ab-av1 {$command->value} needs a CRF. Set one with withCRF().");
    }

    public static function comparisonNeedsTwoFiles(AbAv1Command $command): self
    {
        return new self("ab-av1 {$command->value} compares two files. Open the reference first and the distorted file second, e.g. open('original.mp4', 'encoded.mp4').");
    }

    public static function missingOutput(string $path): self
    {
        return new self("ab-av1 finished without writing {$path}.");
    }
}
