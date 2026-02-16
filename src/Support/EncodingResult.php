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
        // Parse VMAF score if available
        if (preg_match('/VMAF score:\s*([\d.]+)/i', $this->rawOutput, $matches)) {
            $this->vmafScore = (float) $matches[1];
        }

        // Parse XPSNR score if available
        if (preg_match('/XPSNR score:\s*([\d.]+)/i', $this->rawOutput, $matches)) {
            $this->xpsnrScore = (float) $matches[1];
        }

        // Parse CRF value if available
        if (preg_match('/crf:\s*(\d+)/i', $this->rawOutput, $matches)) {
            $this->crfUsed = (int) $matches[1];
        }

        // Parse estimated encode size
        if (preg_match('/estimated.*?size:\s*([\d.]+)\s*([KMGT]?B)/i', $this->rawOutput, $matches)) {
            $this->estimatedSize = $this->parseSizeToBytes($matches[1].$matches[2]);
        }

        // Parse estimated time
        if (preg_match('/estimated.*?time:\s*([^,\n]+)/i', $this->rawOutput, $matches)) {
            $this->estimatedTime = $this->parseTimeToSeconds($matches[1]);
        }
    }

    protected function parseSizeToBytes(string $size): int
    {
        $size = strtoupper(trim($size));
        $multipliers = [
            'TB' => 1024 ** 4,
            'GB' => 1024 ** 3,
            'MB' => 1024 ** 2,
            'KB' => 1024,
            'B' => 1,
        ];

        foreach ($multipliers as $unit => $multiplier) {
            if (str_ends_with($size, $unit)) {
                $value = (float) substr($size, 0, -strlen($unit));

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
