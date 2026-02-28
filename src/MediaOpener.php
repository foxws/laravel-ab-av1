<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Filesystem\Disk;
use Foxws\AbAv1\Filesystem\Media;
use Foxws\AbAv1\Filesystem\TemporaryDirectories;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaOpener
{
    use ForwardsCalls;

    protected ?string $defaultDisk = null;

    protected ?Disk $disk = null;

    protected ?Media $media = null;

    protected ?Encoder $encoder = null;

    public function __construct(?string $defaultDisk = null, ?Encoder $encoder = null)
    {
        $this->defaultDisk = $defaultDisk;
        $this->encoder = $encoder;
    }

    protected function encoder(): Encoder
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        return $this->encoder = app(Encoder::class);
    }

    public function fromDisk(string $disk): self
    {
        $this->disk = Disk::make($disk);

        return $this;
    }

    public function open(string $path): self
    {
        $disk = $this->disk ?? Disk::make($this->defaultDisk ?? config('filesystems.default'));

        $this->media = $disk->makeMedia($path);

        // Set the input path on the encoder
        $encoder = $this->encoder();
        $localPath = $this->media->getLocalPath();

        $encoder->setInputPath($localPath);
        $encoder->getBuilder()->withInput($localPath);

        return $this;
    }

    /**
     * Clean up all temporary files created during this session.
     */
    public function cleanupTemporaryFiles(): self
    {
        app(TemporaryDirectories::class)->deleteAll();

        return $this;
    }

    /**
     * Forward method calls to the encoder, returning $this for fluent chaining.
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->forwardCallTo($encoder = $this->encoder(), $method, $parameters);

        return ($result === $encoder) ? $this : $result;
    }
}
