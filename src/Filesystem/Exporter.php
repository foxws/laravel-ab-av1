<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Filesystem;

use Foxws\AbAv1\Support\EncodingResult;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * Export result to configured disk and path (like Laravel Streamer)
 */
class Exporter
{
    use ForwardsCalls;

    protected ?string $disk = null;

    protected ?string $path = null;

    protected ?string $visibility = null;

    protected ?string $outputFile = null;

    protected FilesystemContract $filesystem;

    protected Encoder $encoder;

    protected array $afterSavingCallbacks = [];

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
     * Set the visibility for the exported file
     */
    public function withVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Returns the final command that would be executed, useful for debugging purposes.
     */
    public function getCommand(): string
    {
        return (string) $this->encoder->getBuilder()->build();
    }

    /**
     * Dump the final command and end the script.
     */
    public function dd(): void
    {
        dd($this->getCommand());
    }

    /**
     * Adds a callable that is invoked after the encoded file is saved.
     * The callback receives this Exporter instance and the EncodingResult.
     */
    public function afterSaving(callable $callback): self
    {
        $this->afterSavingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Run all registered after-saving callbacks and clear the queue.
     */
    protected function runAfterSavingCallbacks(EncodingResult $result): void
    {
        if (empty($this->afterSavingCallbacks)) {
            return;
        }

        foreach ($this->afterSavingCallbacks as $key => $callback) {
            call_user_func($callback, $this, $result);

            unset($this->afterSavingCallbacks[$key]);
        }
    }

    /**
     * Save the encoded file to the configured disk and path.
     * This triggers the actual encoding.
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

            $result = $this->encoder->autoEncode();

            if (! file_exists($this->outputFile)) {
                throw new \RuntimeException('Encoding failed: output file not found');
            }

            // Ensure directory exists
            $directory = dirname($this->path);

            if ($directory !== '.') {
                $this->filesystem->makeDirectory($directory);
            }

            // Copy file to destination
            $stream = fopen($this->outputFile, 'rb');

            $this->filesystem->writeStream($this->path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Apply visibility if set
            if ($this->visibility) {
                $this->filesystem->setVisibility($this->path, $this->visibility);
            }

            $this->runAfterSavingCallbacks($result);

            return true;
        } finally {
            // Always cleanup temporary files
            $this->encoder->cleanupTemporaryFiles();
        }
    }

    /**
     * Get the output file path
     */
    public function getOutputPath(): ?string
    {
        return $this->outputFile;
    }

    /**
     * Forward method calls to the encoder for fluent chaining.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $result = $this->forwardCallTo($encoder = $this->encoder, $method, $arguments);

        return ($result === $encoder) ? $this : $result;
    }
}
