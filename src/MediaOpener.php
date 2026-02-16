<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Filesystem\Disk;
use Foxws\AbAv1\Filesystem\Media;
use Foxws\AbAv1\Support\Encoder;

class MediaOpener
{
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

    public function path(string $path): self
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
     * Forward method calls to the encoder
     */
    public function __call($method, $parameters)
    {
        return $this->encoder()->$method(...$parameters);
    }
}
