<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Filesystem;

use Foxws\AbAv1\Support\Encoder;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Facades\Storage;

/**
 * Export result to configured disk and path (like Laravel Streamer)
 */
class Exporter
{
    protected ?string $disk = null;

    protected ?string $path = null;

    protected ?string $outputFile = null;

    protected FilesystemContract $filesystem;

    protected Encoder $encoder;

    public function __construct(Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * Set the disk to export to
     */
    public function toDisk(string $disk): self
    {
        $this->disk = $disk;
        $this->filesystem = Storage::disk($disk);

        return $this;
    }

    /**
     * Set the path to export to
     */
    public function toPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Save the encoded file to the configured disk and path
     * This triggers the actual encoding (like Laravel Streamer)
     */
    public function save(): bool
    {
        if (! $this->disk || ! $this->path) {
            throw new \RuntimeException('Disk and path must be set before saving');
        }

        try {
            // Generate temporary output file
            $tempDir = $this->encoder->getTemporaryDirectories()->create();
            $this->outputFile = $tempDir.'/output.mp4';

            // Set output on encoder and execute encoding
            $this->encoder->withOutput($this->outputFile);
            $this->encoder->autoEncode();

            if (! file_exists($this->outputFile)) {
                throw new \RuntimeException('Encoding failed: output file not found');
            }

            // Ensure directory exists
            $directory = dirname($this->path);

            if ($directory !== '.') {
                $this->filesystem->makeDirectory($directory, 0755, true, true);
            }

            // Copy file to destination
            $contents = file_get_contents($this->outputFile);

            $this->filesystem->put($this->path, $contents);

            return true;
        } finally {
            // Always cleanup temporary files
            $this->encoder->cleanupTemporaryFiles();
        }
    }

    /**
     * Get the output file path
     */
    public function getOutputPath(): string
    {
        return $this->outputFile;
    }
}
