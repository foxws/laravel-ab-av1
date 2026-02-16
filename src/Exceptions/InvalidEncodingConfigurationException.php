<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Exceptions;

class InvalidEncodingConfigurationException extends EncodingException
{
    public static function minVMAFRequired(): self
    {
        return new self('--min-vmaf option is required for auto-encode command. Use withMinVMAF() method.');
    }

    public static function presetRequired(): self
    {
        return new self('--preset option is required. Use withPreset() method.');
    }

    public static function inputRequired(): self
    {
        return new self('Input file is required. Use withInput() method.');
    }
}
