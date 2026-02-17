<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Exceptions;

class ExecutableNotFoundException extends EncodingException
{
    public static function abAv1NotFound(): self
    {
        return new self('ab-av1 executable not found in PATH. Please install ab-av1: https://github.com/alexheretic/ab-av1');
    }

    public static function ffmpegNotFound(): self
    {
        return new self('ffmpeg executable not found in PATH. Please install ffmpeg with av1 support.');
    }
}
