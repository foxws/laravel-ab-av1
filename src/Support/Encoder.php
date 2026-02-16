<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Support;

use Foxws\AbAv1\Events\EncodingCompleted;
use Foxws\AbAv1\Events\EncodingFailed;
use Foxws\AbAv1\Events\EncodingStarted;
use Foxws\AbAv1\Exceptions\ExecutableNotFoundException;
use Foxws\AbAv1\Exceptions\InvalidEncodingConfigurationException;
use Illuminate\Support\Facades\Process;
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

    protected int $timeout = 3600;

    protected ?string $inputPath = null;

    protected ?string $outputPath = null;

    protected array $ffmpegOptions = [];

    protected array $encInputOptions = [];

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->builder = CommandBuilder::make();
    }

    public static function create(?LoggerInterface $logger = null): self
    {
        return new self($logger);
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

    public function withPreset(string $preset): self
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

    public function withEncoder(string $encoder): self
    {
        $this->builder->withEncoder($encoder);

        return $this;
    }

    public function withEncoders(array $encoders): self
    {
        $this->builder->withEncoders($encoders);

        return $this;
    }

    public function withFFmpegOption(string $key, mixed $value): self
    {
        $this->ffmpegOptions[$key] = $value;
        $this->builder->withOption("enc-input-{$key}", $value);

        return $this;
    }

    public function withFFmpegOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->withFFmpegOption($key, $value);
        }

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
            $process = Process::timeout($this->timeout)->run($command);

            $executionTime = microtime(true) - $startTime;

            if (! $process->successful()) {
                throw new \RuntimeException("ab-av1 command failed: {$process->errorOutput()}");
            }

            $output = $process->output();
            $result = new EncodingResult($this->inputPath ?? 'unknown', $output);

            if ($this->outputPath) {
                $result->setOutputPath($this->outputPath);
            }

            if ($this->logger) {
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
        $abAv1Result = Process::run('which ab-av1');
        if (! $abAv1Result->successful()) {
            throw ExecutableNotFoundException::abAv1NotFound();
        }

        $ffmpegResult = Process::run('which ffmpeg');
        if (! $ffmpegResult->successful()) {
            throw ExecutableNotFoundException::ffmpegNotFound();
        }
    }
}
