<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\Media\Process\Progress;
use Foxws\Media\Process\ProgressParser;

/**
 * Parses the progress ab-av1 logs on its error output while it encodes the whole file, when that isn't
 * a terminal, e.g. "[2026-10-06T12:00:00Z INFO  ab_av1::command::encode] 42%, 24.5 fps, eta 3 minutes".
 * ab-av1 logs it after 16, 32, 64 seconds and so on, doubling each time.
 */
class AbAv1ProgressParser implements ProgressParser
{
    protected const string PATTERN = '/ab_av1::command::encode\]\s+(\d+(?:\.\d+)?)%,\s*(\d+(?:\.\d+)?)\s*fps(?:,\s*eta\s+(\d+)\s+(second|minute|hour|day|week)s?)?/';

    protected string $buffer = '';

    public function __construct(protected ?float $duration = null) {}

    public function feed(string $output): array
    {
        $this->buffer .= $output;

        $updates = [];

        while (($newline = strpos($this->buffer, "\n")) !== false) {
            $line = substr($this->buffer, 0, $newline);
            $this->buffer = substr($this->buffer, $newline + 1);

            if (preg_match(self::PATTERN, $line, $matches) === 1) {
                $updates[] = $this->progress((float) $matches[1], (float) $matches[2], isset($matches[4]) ? $this->seconds((int) $matches[3], $matches[4]) : null);
            }
        }

        return $updates;
    }

    /**
     * The progress with ab-av1's percentage as seconds of the duration, and its estimate turned into a speed.
     */
    protected function progress(float $percent, float $fps, ?float $eta): Progress
    {
        if ($this->duration === null || $this->duration <= 0) {
            return new Progress(seconds: 0.0, fps: $fps);
        }

        $seconds = min($this->duration, $this->duration * $percent / 100);

        return new Progress(
            seconds: $seconds,
            duration: $this->duration,
            speed: $eta !== null && $eta > 0 ? ($this->duration - $seconds) / $eta : null,
            fps: $fps,
        );
    }

    protected function seconds(int $amount, string $unit): float
    {
        return $amount * match ($unit) {
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            default => 1,
        };
    }
}
