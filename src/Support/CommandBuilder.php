<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Support;

use Illuminate\Support\Str;

/**
 * Builds ab-av1 command-line arguments
 *
 * Fluent interface for constructing ab-av1 CLI commands with proper
 * argument validation and escaping.
 */
class CommandBuilder
{
    protected string $command = 'ab-av1';

    protected array $arguments = [];

    public static function make(): self
    {
        return new self;
    }

    public function autoEncode(): self
    {
        $this->arguments['command'] = 'auto-encode';

        return $this;
    }

    public function crfSearch(): self
    {
        $this->arguments['command'] = 'crf-search';

        return $this;
    }

    public function sampleEncode(): self
    {
        $this->arguments['command'] = 'sample-encode';

        return $this;
    }

    public function encode(): self
    {
        $this->arguments['command'] = 'encode';

        return $this;
    }

    public function vmaf(): self
    {
        $this->arguments['command'] = 'vmaf';

        return $this;
    }

    public function xpsnr(): self
    {
        $this->arguments['command'] = 'xpsnr';

        return $this;
    }

    public function withInput(string $path): self
    {
        $this->arguments['input'] = $path;

        return $this;
    }

    public function withOutput(string $path): self
    {
        $this->arguments['output'] = $path;

        return $this;
    }

    public function withCRF(int $crf): self
    {
        if ($crf < 0 || $crf > 63) {
            throw new \InvalidArgumentException("CRF value must be between 0 and 63, got {$crf}");
        }

        $this->arguments['crf'] = $crf;

        return $this;
    }

    public function withPreset(string $preset): self
    {
        $validPresets = ['ultrafast', 'superfast', 'veryfast', 'faster', 'fast', 'medium', 'slow', 'slower'];

        if (! in_array($preset, $validPresets)) {
            throw new \InvalidArgumentException("Invalid preset '{$preset}'. Must be one of: ".implode(', ', $validPresets));
        }

        $this->arguments['preset'] = $preset;

        return $this;
    }

    public function withMinVMAF(float $vmaf): self
    {
        $this->arguments['min-vmaf'] = $vmaf;

        return $this;
    }

    public function withMinXPSNR(float $xpsnr): self
    {
        $this->arguments['min-xpsnr'] = $xpsnr;

        return $this;
    }

    public function withMaxEncodedPercent(int $percent): self
    {
        $this->arguments['max-encoded-percent'] = $percent;

        return $this;
    }

    public function withEncoder(string $encoder): self
    {
        $this->arguments['encoder'] = $encoder;

        return $this;
    }

    public function withEncoders(array $encoders): self
    {
        // Store as comma-separated if multiple
        if (count($encoders) > 1) {
            $this->arguments['encoder'] = implode(',', $encoders);
        } else {
            $this->arguments['encoder'] = $encoders[0] ?? null;
        }

        return $this;
    }

    public function withReferenceFile(string $path): self
    {
        $this->arguments['reference'] = $path;

        return $this;
    }

    public function withDistortedFile(string $path): self
    {
        $this->arguments['distorted'] = $path;

        return $this;
    }

    public function withOption(string $key, mixed $value): self
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    public function withOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->withOption($key, $value);
        }

        return $this;
    }

    public function jsonOutput(bool $enabled = true): self
    {
        if ($enabled) {
            $this->arguments['stdout-format'] = 'json';
        }

        return $this;
    }

    public function build(): string
    {
        $parts = [$this->command];

        // Add command first if present
        if (isset($this->arguments['command'])) {
            $parts[] = $this->arguments['command'];
        }

        // Build remaining arguments
        foreach ($this->arguments as $key => $value) {
            if ($key === 'command') {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $parts[] = "--{$key}";
                }
            } elseif ($value !== null) {
                // Convert keys to kebab-case for CLI compatibility
                $key = Str::kebab($key);
                $stringValue = (string) $value;

                // Only escape if the value contains spaces or special characters
                if (preg_match('/[\s"\'$`\\\\|&;<>()]/', $stringValue)) {
                    $stringValue = escapeshellarg($stringValue);
                }

                $parts[] = "--{$key} {$stringValue}";
            }
        }

        return implode(' ', $parts);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
