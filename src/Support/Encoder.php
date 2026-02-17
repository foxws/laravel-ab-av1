<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Support;

use Foxws\AbAv1\Events\EncodingCompleted;
use Foxws\AbAv1\Events\EncodingFailed;
use Foxws\AbAv1\Events\EncodingStarted;
use Foxws\AbAv1\Exceptions\ExecutableNotFoundException;
use Foxws\AbAv1\Exceptions\InvalidEncodingConfigurationException;
use Foxws\AbAv1\Filesystem\Exporter;
use Foxws\AbAv1\Filesystem\TemporaryDirectories;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

/**
 * ab-av1 Encoder
 *
 * Provides a fluent interface for executing ab-av1 encoding operations.
 * Wraps ab-av1 CLI commands with error handling and event dispatching.
 */
class Encoder
{
    protected CommandBuilder $builder;

    protected ?LoggerInterface $logger;

    protected int $timeout = 14400;

    protected ?string $inputPath = null;

    protected ?string $outputPath = null;

    protected ?string $disk = null;

    protected ?Filesystem $filesystem = null;

    protected ?TemporaryDirectories $temporaryDirectories = null;

    protected string $binary = 'ab-av1';

    public function __construct(?LoggerInterface $logger = null, ?TemporaryDirectories $temporaryDirectories = null, ?int $timeout = null, ?string $binary = null)
    {
        $this->logger = $logger;
        $this->binary = $binary ?? 'ab-av1';
        $this->builder = CommandBuilder::make($this->binary);
        $this->temporaryDirectories = $temporaryDirectories ?? app(TemporaryDirectories::class);
        $this->timeout = $timeout ?? 14400;
    }

    public static function create(?LoggerInterface $logger = null, ?TemporaryDirectories $temporaryDirectories = null, ?int $timeout = null, array $config = []): self
    {
        $binary = $config['binary'] ?? 'ab-av1';

        $encoder = new self($logger, $temporaryDirectories, $timeout, $binary);

        // Apply configuration defaults
        if (filled($config['preset'] ?? null)) {
            $encoder->withPreset($config['preset']);
        }

        if (filled($config['min_vmaf'] ?? null)) {
            $encoder->withMinVMAF($config['min_vmaf']);
        }

        if (filled($config['max_encoded_percent'] ?? null)) {
            $encoder->withMaxEncodedPercent($config['max_encoded_percent']);
        }

        if (filled($config['vframes'] ?? null)) {
            $encoder->withVFrames($config['vframes']);
        }

        if (filled($config['samples'] ?? null)) {
            $encoder->withSamples($config['samples']);
        }

        if (filled($config['encoder'] ?? null)) {
            $encoder->withEncoder($config['encoder']);
        }

        if (filled($config['encoder_args'] ?? null)) {
            $encoder->withEncoderArgs($config['encoder_args']);
        }

        if (filled($config['pix_format'] ?? null)) {
            $encoder->withPixelFormat($config['pix_format']);
        }

        if (filled($config['video_filter'] ?? null)) {
            $encoder->withVideoFilter($config['video_filter']);
        }

        if (filled($config['verbosity'] ?? null)) {
            $encoder->withVerbosity($config['verbosity']);
        }

        if (filled($config['ffmpeg_input_options'] ?? null)) {
            $encoder->withFFmpegOptions($config['ffmpeg_input_options']);
        }

        return $encoder;
    }

    /**
     * Set the input path internally (used by MediaOpener)
     */
    public function setInputPath(string $path): void
    {
        $this->inputPath = $path;
    }

    /**
     * Set the disk to use for media operations (like Laravel Streamer)
     */
    public function fromDisk(string $disk): self
    {
        $this->disk = $disk;
        $this->filesystem = Storage::disk($disk);

        if ($this->logger) {
            $this->logger->debug('Set disk', ['disk' => $disk]);
        }

        return $this;
    }

    /**
     * Set the input file path relative to the disk
     */
    public function path(string $path): self
    {
        if (! $this->filesystem) {
            throw new \RuntimeException('Disk must be set before setting path. Use fromDisk() first.');
        }

        if (! $this->filesystem->exists($path)) {
            throw new \RuntimeException("Input file not found on disk '{$this->disk}': {$path}");
        }

        // Get raw path of the file
        $fullPath = $this->filesystem->path($path);
        $this->inputPath = $fullPath;
        $this->builder->withInput($fullPath);

        if ($this->logger) {
            $this->logger->debug('Set input path', ['path' => $path, 'full_path' => $fullPath]);
        }

        return $this;
    }

    public function getBuilder(): CommandBuilder
    {
        return $this->builder;
    }

    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set input file path directly (for backward compatibility or when not using disk/path)
     */
    public function withInput(string $path): self
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Input file not found: {$path}");
        }

        $this->inputPath = $path;
        $this->builder->withInput($path);

        if ($this->logger) {
            $this->logger->debug('Set input path', ['path' => $path]);
        }

        return $this;
    }

    public function withOutput(string $path): self
    {
        $this->outputPath = $path;
        $this->builder->withOutput($path);

        return $this;
    }

    public function withCRF(int $crf): self
    {
        $this->builder->withCRF($crf);

        return $this;
    }

    public function withPreset(int|string $preset): self
    {
        $this->builder->withPreset($preset);

        return $this;
    }

    public function withMinVMAF(float $vmaf): self
    {
        $this->builder->withMinVMAF($vmaf);

        return $this;
    }

    public function withMinXPSNR(float $xpsnr): self
    {
        $this->builder->withMinXPSNR($xpsnr);

        return $this;
    }

    public function withMaxEncodedPercent(int $percent): self
    {
        $this->builder->withMaxEncodedPercent($percent);

        return $this;
    }

    public function withVFrames(int $frames): self
    {
        $this->builder->withVFrames($frames);

        return $this;
    }

    public function withSamples(int $samples): self
    {
        $this->builder->withSamples($samples);

        return $this;
    }

    public function withEncoder(string $encoder): self
    {
        $this->builder->withEncoder($encoder);

        return $this;
    }

    public function withEncoderArgs(string $args): self
    {
        $this->builder->withEncoderArgs($args);

        return $this;
    }

    public function withPixelFormat(string $format): self
    {
        $this->builder->withPixelFormat($format);

        return $this;
    }

    public function withVideoFilter(string $filter): self
    {
        $this->builder->withVideoFilter($filter);

        return $this;
    }

    public function withVerbosity(int $level = 1): self
    {
        $this->builder->withVerbosity($level);

        return $this;
    }

    public function withEncoders(array $encoders): self
    {
        $this->builder->withEncoders($encoders);

        return $this;
    }

    public function withFFmpegOptions(array|string $options): self
    {
        // Convert array format to space-separated string
        if (is_array($options)) {
            $options = Collection::make($options)
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(' ');
        }

        // Parse space-separated FFmpeg input options
        // Example: "hwaccel=vaapi hwaccel_output_format=vaapi"
        // Results in: --enc-input hwaccel=vaapi --enc-input hwaccel_output_format=vaapi
        $encInputOptions = preg_split('/\s+/', trim($options), -1, PREG_SPLIT_NO_EMPTY);

        // Merge with existing enc-input options instead of overwriting
        $existing = $this->builder->getArguments()['enc-input'] ?? [];

        if (! is_array($existing)) {
            $existing = [$existing];
        }

        $merged = array_merge($existing, $encInputOptions);
        $this->builder->withOption('enc-input', $merged);

        return $this;
    }

    public function withOption(string $key, mixed $value): self
    {
        $this->builder->withOption($key, $value);

        return $this;
    }

    public function withOptions(array $options): self
    {
        $this->builder->withOptions($options);

        return $this;
    }

    public function jsonOutput(): self
    {
        $this->builder->jsonOutput(true);

        return $this;
    }

    /**
     * Execute auto-encode command
     *
     * Automatically determine best CRF value to meet VMAF target
     */
    public function autoEncode(): EncodingResult
    {
        $this->validateAutoEncodeConfiguration();

        return $this->executeCommand('auto-encode');
    }

    /**
     * Execute crf-search command
     *
     * Binary search to find optimal CRF value
     */
    public function crfSearch(): EncodingResult
    {
        $this->validateAutoEncodeConfiguration();

        return $this->executeCommand('crf-search');
    }

    /**
     * Execute sample-encode command
     *
     * Quick sample encoding for testing
     */
    public function sampleEncode(): EncodingResult
    {
        $this->validateSampleEncodeConfiguration();

        return $this->executeCommand('sample-encode');
    }

    /**
     * Execute encode command
     *
     * Full video encoding with specified CRF
     */
    public function encode(): EncodingResult
    {
        $this->validateEncodeConfiguration();

        return $this->executeCommand('encode');
    }

    /**
     * Execute vmaf command
     *
     * Calculate full VMAF score
     */
    public function vmaf(string $referenceFile, string $distortedFile): EncodingResult
    {
        if (! file_exists($referenceFile)) {
            throw new \RuntimeException("Reference file not found: {$referenceFile}");
        }

        if (! file_exists($distortedFile)) {
            throw new \RuntimeException("Distorted file not found: {$distortedFile}");
        }

        $this->builder->vmaf()
            ->withReferenceFile($referenceFile)
            ->withDistortedFile($distortedFile);

        return $this->executeCommand('vmaf');
    }

    /**
     * Execute xpsnr command
     *
     * Calculate full XPSNR score
     */
    public function xpsnr(string $referenceFile, string $distortedFile): EncodingResult
    {
        if (! file_exists($referenceFile)) {
            throw new \RuntimeException("Reference file not found: {$referenceFile}");
        }

        if (! file_exists($distortedFile)) {
            throw new \RuntimeException("Distorted file not found: {$distortedFile}");
        }

        $this->builder->xpsnr()
            ->withReferenceFile($referenceFile)
            ->withDistortedFile($distortedFile);

        return $this->executeCommand('xpsnr');
    }

    /**
     * Create an exporter for saving encoded files (like Laravel Streamer)
     * Automatically creates temporary output file
     */
    public function export(): Exporter
    {
        return new Exporter($this);
    }

    /**
     * Get temporary directories instance
     */
    public function getTemporaryDirectories(): TemporaryDirectories
    {
        return $this->temporaryDirectories;
    }

    /**
     * Clean up temporary directories
     */
    public function cleanupTemporaryFiles(): void
    {
        if ($this->temporaryDirectories) {
            $this->temporaryDirectories->deleteAll();
        }
    }

    /**
     * Execute a command
     */
    protected function executeCommand(string $command): EncodingResult
    {
        $this->validateExecutablesExist();

        $command = $this->builder->build();

        if ($this->logger) {
            $this->logger->info("Executing ab-av1 command: {$command}");
        }

        EncodingStarted::dispatch($this->inputPath ?? 'unknown', $this->builder->getArguments());

        $startTime = microtime(true);

        try {
            // Change to output directory to prevent temp files in project root
            $workingDirectory = $this->outputPath ? dirname($this->outputPath) : null;

            $process = Process::timeout($this->timeout)
                ->path($workingDirectory)
                ->run($command);

            $executionTime = microtime(true) - $startTime;

            if (! $process->successful()) {
                throw new \RuntimeException("ab-av1 command failed: {$process->errorOutput()}");
            }

            // ab-av1 outputs logs to stderr, not stdout
            $output = $process->errorOutput() ?: $process->output();
            $result = new EncodingResult($this->inputPath ?? 'unknown', $output);

            if ($this->outputPath) {
                $result->setOutputPath($this->outputPath);
            }

            if ($this->logger) {
                // Debug: Log raw output to help debug parsing
                $this->logger->debug('Raw ab-av1 output', [
                    'stdout' => $process->output(),
                    'stderr' => $process->errorOutput(),
                ]);

                $this->logger->info('Encoding completed', [
                    'vmaf_score' => $result->getVMAFScore(),
                    'crf_used' => $result->getCRFUsed(),
                    'execution_time' => $executionTime,
                ]);
            }

            EncodingCompleted::dispatch($result, $executionTime);

            return $result;
        } catch (\Throwable $exception) {
            $executionTime = microtime(true) - $startTime;

            if ($this->logger) {
                $this->logger->error('Encoding failed', [
                    'exception' => $exception->getMessage(),
                    'execution_time' => $executionTime,
                ]);
            }

            EncodingFailed::dispatch($this->inputPath ?? 'unknown', $exception);

            throw $exception;
        }
    }

    protected function validateAutoEncodeConfiguration(): void
    {
        if (! $this->inputPath) {
            throw InvalidEncodingConfigurationException::inputRequired();
        }

        $args = $this->builder->getArguments();

        if (! isset($args['preset'])) {
            throw InvalidEncodingConfigurationException::presetRequired();
        }

        if (! isset($args['min-vmaf']) && ! isset($args['min-xpsnr'])) {
            throw InvalidEncodingConfigurationException::minVMAFRequired();
        }

        $this->builder->autoEncode();
    }

    protected function validateEncodeConfiguration(): void
    {
        if (! $this->inputPath) {
            throw InvalidEncodingConfigurationException::inputRequired();
        }

        $args = $this->builder->getArguments();

        if (! isset($args['crf'])) {
            throw new InvalidEncodingConfigurationException('--crf option is required for encode command. Use withCRF() method.');
        }

        if (! isset($args['preset'])) {
            throw InvalidEncodingConfigurationException::presetRequired();
        }

        $this->builder->encode();
    }

    protected function validateSampleEncodeConfiguration(): void
    {
        if (! $this->inputPath) {
            throw InvalidEncodingConfigurationException::inputRequired();
        }

        $args = $this->builder->getArguments();

        if (! isset($args['crf'])) {
            throw new InvalidEncodingConfigurationException('--crf option is required for sample-encode command. Use withCRF() method.');
        }

        if (! isset($args['preset'])) {
            throw InvalidEncodingConfigurationException::presetRequired();
        }

        $this->builder->sampleEncode();
    }

    protected function validateExecutablesExist(): void
    {
        $abAv1Result = Process::run("which {$this->binary}");

        if (! $abAv1Result->successful()) {
            throw ExecutableNotFoundException::abAv1NotFound();
        }

        $ffmpegResult = Process::run('which ffmpeg');
        if (! $ffmpegResult->successful()) {
            throw ExecutableNotFoundException::ffmpegNotFound();
        }
    }
}
