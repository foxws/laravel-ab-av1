<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Main ab-av1 facade wrapper
 *
 * Provides convenient access to ab-av1 encoding functionality.
 */
class AbAv1
{
    public function __construct(
        protected ?LoggerInterface $logger = null
    ) {
        $this->logger ??= Log::channel(config('ab-av1.log_channel', 'stack'));
    }

    /**
     * Create a new encoder instance
     */
    public function encoder(): Encoder
    {
        $encoder = Encoder::create($this->logger);
        $encoder->setTimeout(config('ab-av1.timeout', 3600));

        return $encoder;
    }

    /**
     * Create a new encoder with default configuration applied
     */
    public function withDefaults(): Encoder
    {
        $encoder = $this->encoder();

        // Apply default preset if configured
        if ($preset = config('ab-av1.preset')) {
            $encoder->withPreset($preset);
        }

        // Apply default encoders if configured
        if ($encoders = config('ab-av1.encoders')) {
            $encoder->withEncoders((array) $encoders);
        }

        // Apply default min VMAF if configured
        if ($minVmaf = config('ab-av1.min_vmaf')) {
            $encoder->withMinVMAF($minVmaf);
        }

        // Apply FFmpeg input options if configured
        if ($ffmpegOptions = config('ab-av1.ffmpeg_input_options', [])) {
            $encoder->withFFmpegOptions($ffmpegOptions);
        }

        return $encoder;
    }

    /**
     * Fluent encoder creation
     */
    public static function encode(): Encoder
    {
        return Encoder::create();
    }

    /**
     * Get logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set logger instance
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
