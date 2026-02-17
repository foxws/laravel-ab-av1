<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Support;

/**
 * Result from ab-av1 encoding operation
 *
 * Holds parsed output from ab-av1 commands including VMAF scores,
 * file sizes, encoding times, and other metrics.
 */
class EncodingResult
{
    protected string $inputPath;

    protected ?string $outputPath = null;

    protected ?float $vmafScore = null;

    protected ?float $xpsnrScore = null;

    protected ?int $crfUsed = null;

    protected ?int $estimatedSize = null;

    protected ?float $estimatedTime = null;

    protected string $rawOutput;

    protected array $metadata = [];

    public function __construct(
        string $inputPath,
        string $rawOutput,
        array $metadata = []
    ) {
        $this->inputPath = $inputPath;
        $this->rawOutput = $rawOutput;
        $this->metadata = $metadata;

        $this->parseOutput();
    }

    protected function parseOutput(): void
    {
        // Parse ab-av1 output format: "crf 20 VMAF 94.80 predicted video stream size..."
        // ab-av1 tests multiple CRF values, so we need to get the last/final one

        // Parse all VMAF scores and get the last one (final chosen value)
        // Format: "crf 20 VMAF 94.80"
        if (preg_match_all('/crf\s+\d+\s+VMAF\s+([\d.]+)/i', $this->rawOutput, $matches)) {
            $this->vmafScore = (float) end($matches[1]);
        }

        // Parse all CRF values and get the last one (final chosen value)
        // Format: "crf 20 VMAF 94.80"
        if (preg_match_all('/crf\s+(\d+)\s+VMAF/i', $this->rawOutput, $matches)) {
            $this->crfUsed = (int) end($matches[1]);
        }

        // Parse estimated encode size from ab-av1 output (get last occurrence)
        // Format: "predicted video stream size 174.92 MiB"
        if (preg_match_all('/predicted\s+video\s+stream\s+size\s+([\d.]+)\s+([KMGT]?iB)/i', $this->rawOutput, $matches)) {
            $lastSize = end($matches[1]);
            $lastUnit = end($matches[2]);

            $this->estimatedSize = $this->parseSizeToBytes($lastSize.$lastUnit);
        }

        // Parse estimated time from ab-av1 output (get last occurrence)
        // Format: "taking 81 seconds"
        if (preg_match_all('/taking\s+(\d+)\s+seconds/i', $this->rawOutput, $matches)) {
            $this->estimatedTime = (float) end($matches[1]);
        }

        // Fallback: Parse XPSNR score if available
        if (preg_match('/XPSNR\s+([\d.]+)/i', $this->rawOutput, $matches)) {
            $this->xpsnrScore = (float) $matches[1];
        }
    }

    protected function parseSizeToBytes(string $size): int
    {
        $size = trim($size);

        // Binary units (IEC standard used by ab-av1)
        $binaryMultipliers = [
            'TiB' => 1024 ** 4,
            'GiB' => 1024 ** 3,
            'MiB' => 1024 ** 2,
            'KiB' => 1024,
        ];

        // Decimal units (SI standard)
        $decimalMultipliers = [
            'TB' => 1000 ** 4,
            'GB' => 1000 ** 3,
            'MB' => 1000 ** 2,
            'KB' => 1000,
            'B' => 1,
        ];

        // Try binary units first (ab-av1 uses these)
        foreach ($binaryMultipliers as $unit => $multiplier) {
            if (stripos($size, $unit) !== false) {
                $value = (float) str_ireplace($unit, '', $size);

                return (int) ($value * $multiplier);
            }
        }

        // Fallback to decimal units
        foreach ($decimalMultipliers as $unit => $multiplier) {
            if (stripos($size, $unit) !== false) {
                $value = (float) str_ireplace($unit, '', $size);

                return (int) ($value * $multiplier);
            }
        }

        return (int) $size;
    }

    protected function parseTimeToSeconds(string $time): float
    {
        $time = strtolower(trim($time));
        $seconds = 0;

        if (preg_match('/(\d+)h/', $time, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)m(?!s)/', $time, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+(?:\.\d+)?)s/', $time, $m)) {
            $seconds += (float) $m[1];
        }

        return (float) $seconds;
    }

    public function getInputPath(): string
    {
        return $this->inputPath;
    }

    public function setOutputPath(string $path): self
    {
        $this->outputPath = $path;

        return $this;
    }

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function getVMAFScore(): ?float
    {
        return $this->vmafScore;
    }

    public function setVMAFScore(float $score): self
    {
        $this->vmafScore = $score;

        return $this;
    }

    public function getXPSNRScore(): ?float
    {
        return $this->xpsnrScore;
    }

    public function setXPSNRScore(float $score): self
    {
        $this->xpsnrScore = $score;

        return $this;
    }

    public function getCRFUsed(): ?int
    {
        return $this->crfUsed;
    }

    public function setCRFUsed(int $crf): self
    {
        $this->crfUsed = $crf;

        return $this;
    }

    public function getEstimatedSize(): ?int
    {
        return $this->estimatedSize;
    }

    public function setEstimatedSize(int $size): self
    {
        $this->estimatedSize = $size;

        return $this;
    }

    public function getEstimatedTime(): ?float
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(float $seconds): self
    {
        $this->estimatedTime = $seconds;

        return $this;
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getRawOutput(): string
    {
        return $this->rawOutput;
    }

    public function toArray(): array
    {
        return [
            'input_path' => $this->inputPath,
            'output_path' => $this->outputPath,
            'vmaf_score' => $this->vmafScore,
            'xpsnr_score' => $this->xpsnrScore,
            'crf_used' => $this->crfUsed,
            'estimated_size' => $this->estimatedSize,
            'estimated_time' => $this->estimatedTime,
            'metadata' => $this->metadata,
        ];
    }
}
