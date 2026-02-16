<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Filesystem;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Facades\Storage;

/**
 * Export result to configured disk and path
 */
class Exporter
{
    protected ?string $disk = null;

    protected ?string $path = null;

    protected string $outputFile;

    protected FilesystemContract $filesystem;

    public function __construct(string $outputFile)
    {
        $this->outputFile = $outputFile;
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
     */
    public function save(): bool
    {
        if (! $this->disk || ! $this->path) {
            throw new \RuntimeException('Disk and path must be set before saving');
        }

        if (! file_exists($this->outputFile)) {
            throw new \RuntimeException("Output file not found: {$this->outputFile}");
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
    }

    /**
     * Get the output file path
     */
    public function getOutputPath(): string
    {
        return $this->outputFile;
    }
}
