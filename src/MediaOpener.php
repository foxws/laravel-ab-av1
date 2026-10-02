<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Filesystem\Disk;
use Foxws\AbAv1\Filesystem\Media;
use Foxws\AbAv1\Filesystem\TemporaryDirectories;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @method void setInputPath(string $path)
 * @method $this path(string $path)
 * @method \Foxws\AbAv1\Support\CommandBuilder getBuilder()
 * @method $this setLogger(?\Psr\Log\LoggerInterface $logger)
 * @method $this setTimeout(int $seconds)
 * @method int getTimeout()
 * @method $this withInput(string $path)
 * @method $this withOutput(string $path)
 * @method $this withCRF(int|float $crf)
 * @method $this withPreset(int|string $preset)
 * @method $this withMinVMAF(float $vmaf)
 * @method $this withMinXPSNR(float $xpsnr)
 * @method $this withMaxEncodedPercent(int $percent)
 * @method $this withVFrames(int $frames)
 * @method $this withSamples(int $samples)
 * @method $this withEncoder(string $encoder)
 * @method $this withEncoderArgs(string $args)
 * @method $this withPixelFormat(string $format)
 * @method $this withVideoFilter(string $filter)
 * @method $this withVerbosity(int $level = 1)
 * @method $this withEncoders(array<int, string> $encoders)
 * @method $this withFFmpegOptions(array<string, string>|string $options)
 * @method $this withOption(string $key, mixed $value)
 * @method $this withOptions(array<string, mixed> $options)
 * @method $this withVerify(bool $enabled = true)
 * @method $this withFailFast(bool $enabled = true)
 * @method $this jsonOutput()
 * @method \Foxws\AbAv1\Support\EncodingResult autoEncode()
 * @method \Foxws\AbAv1\Support\EncodingResult crfSearch()
 * @method \Foxws\AbAv1\Support\EncodingResult sampleEncode()
 * @method \Foxws\AbAv1\Support\EncodingResult encode()
 * @method \Foxws\AbAv1\Support\EncodingResult vmaf(string $referenceFile, string $distortedFile)
 * @method \Foxws\AbAv1\Support\EncodingResult xpsnr(string $referenceFile, string $distortedFile)
 * @method \Foxws\AbAv1\Filesystem\Exporter export()
 * @method \Foxws\AbAv1\Filesystem\TemporaryDirectories getTemporaryDirectories()
 */
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
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->forwardCallTo($encoder = $this->encoder(), $method, $parameters);

        return ($result === $encoder) ? $this : $result;
    }
}
