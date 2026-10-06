<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\Media\Filesystem\ExportResult;

/**
 * What ab-av1 reported: the chosen CRF, the measured quality and the predicted encode, plus the
 * saved file for encodes.
 */
final readonly class AbAv1Result
{
    /**
     * @param  int|null  $predictedSize  The predicted video stream size in bytes.
     * @param  float|null  $predictedPercent  The predicted size as a percentage of the input.
     * @param  float|null  $predictedTime  The predicted encode time in seconds.
     * @param  string  $output  ab-av1's error output and output, for debugging.
     */
    public function __construct(
        public AbAv1Command $command,
        public ?float $crf = null,
        public ?float $vmaf = null,
        public ?float $xpsnr = null,
        public ?int $predictedSize = null,
        public ?float $predictedPercent = null,
        public ?float $predictedTime = null,
        public ?ExportResult $export = null,
        public string $output = '',
    ) {}

    /**
     * Read ab-av1's output, which reports one line per tried CRF; the last one is its choice,
     * e.g. "crf 31 VMAF 95.32 predicted video stream size 105.69 MiB (43%) taking 9 minutes".
     * The vmaf and xpsnr commands print only the score.
     */
    public static function fromOutput(AbAv1Command $command, string $output, ?ExportResult $export = null): self
    {
        $last = fn (string $pattern): ?float => preg_match_all($pattern, $output, $matches) > 0 ? (float) end($matches[1]) : null;

        if ($command->compares()) {
            $score = $last('/^\s*(-?\d+(?:\.\d+)?)\s*$/m');

            return new self(
                command: $command,
                vmaf: $command === AbAv1Command::Vmaf ? $score : null,
                xpsnr: $command === AbAv1Command::Xpsnr ? $score : null,
                output: $output,
            );
        }

        return new self(
            command: $command,
            crf: $last('/\bcrf\s+(\d+(?:\.\d+)?)/i'),
            vmaf: $last('/\bVMAF\s+(\d+(?:\.\d+)?)/'),
            xpsnr: $last('/\bXPSNR\s+(-?\d+(?:\.\d+)?)/'),
            predictedSize: self::bytes($output),
            predictedPercent: $last('/predicted video stream size[^(\n]*\((\d+(?:\.\d+)?)%\)/i'),
            predictedTime: self::seconds($output),
            export: $export,
            output: $output,
        );
    }

    /**
     * The saved file's path on its disk, for encodes.
     */
    public function path(): ?string
    {
        return $this->export?->path();
    }

    protected static function bytes(string $output): ?int
    {
        if (preg_match_all('/predicted video stream size\s+(\d+(?:\.\d+)?)\s*([KMGT]?i?B)\b/i', $output, $matches) === 0) {
            return null;
        }

        $unit = strtoupper((string) end($matches[2]));
        $power = (int) strpos('BKMGT', $unit[0]);
        $base = str_contains($unit, 'I') ? 1024 : 1000;

        return (int) round((float) end($matches[1]) * $base ** $power);
    }

    protected static function seconds(string $output): ?float
    {
        if (preg_match_all('/taking\s+(\d+(?:\.\d+)?)\s+(second|minute|hour)s?/i', $output, $matches) === 0) {
            return null;
        }

        return (float) end($matches[1]) * match (strtolower((string) end($matches[2]))) {
            'hour' => 3600,
            'minute' => 60,
            default => 1,
        };
    }
}
