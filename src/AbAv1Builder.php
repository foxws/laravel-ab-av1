<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\AbAv1\Exceptions\AbAv1Exception;
use Foxws\Media\Concerns\HasContext;
use Foxws\Media\Concerns\HasSaveCallbacks;
use Foxws\Media\Concerns\ReportsProgress;
use Foxws\Media\Encoding\PixelFormat;
use Foxws\Media\Events\ExportCompleted;
use Foxws\Media\Events\ExportFailed;
use Foxws\Media\Events\ProgressReported;
use Foxws\Media\Exceptions\ProcessCancelledException;
use Foxws\Media\Exceptions\ProcessFailedException;
use Foxws\Media\Executables\Executable;
use Foxws\Media\Executables\Executables;
use Foxws\Media\Filesystem\Disk;
use Foxws\Media\Filesystem\Exporter;
use Foxws\Media\Filesystem\ExportResult;
use Foxws\Media\Filesystem\TemporaryDirectories;
use Foxws\Media\Filters\Custom;
use Foxws\Media\Filters\Filter;
use Foxws\Media\Filters\FilterType;
use Foxws\Media\Filters\Number;
use Foxws\Media\Opener;
use Foxws\Media\Process\Progress;
use Foxws\Media\Process\Runner;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Throwable;

/**
 * Runs ab-av1 on the opened media: encode at a target quality, search the CRF that reaches it,
 * test a CRF on samples, or compare two files.
 */
class AbAv1Builder
{
    use Conditionable;
    use HasContext;
    use HasSaveCallbacks;
    use ReportsProgress;

    /**
     * The commands that accept each option. Options missing here, e.g. from withOption(), go to every command.
     *
     * @var array<string, list<AbAv1Command>>
     */
    protected const array COMMAND_OPTIONS = [
        'preset' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'encoder' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'enc' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'enc-input' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'pix-format' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'crf' => [AbAv1Command::SampleEncode, AbAv1Command::Encode],
        'min-vmaf' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch],
        'min-xpsnr' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch],
        'max-encoded-percent' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch],
        'samples' => [AbAv1Command::AutoEncode, AbAv1Command::CrfSearch, AbAv1Command::SampleEncode],
        'verify' => [AbAv1Command::AutoEncode, AbAv1Command::Encode],
        'fail-fast' => [AbAv1Command::AutoEncode, AbAv1Command::Encode],
    ];

    /** @var array<string, string|list<string>|true> */
    protected array $options = [];

    /** @var list<Filter> */
    protected array $filters = [];

    protected ?Disk $targetDisk = null;

    protected ?string $visibility = null;

    protected ?int $timeout = null;

    public function __construct(
        protected Opener $opener,
        protected Runner $runner,
        protected Executables $executables,
        protected TemporaryDirectories $directories,
        protected Exporter $exporter,
    ) {
        $this->applyDefaults();
    }

    /**
     * The encoder preset. For svt-av1, from 0 (slowest, smallest files) to 13 (fastest).
     */
    public function withPreset(int|string $preset): static
    {
        if (is_int($preset) && ($preset < 0 || $preset > 13)) {
            throw new InvalidArgumentException("A numeric preset must be between 0 and 13, not [{$preset}].");
        }

        if ($preset === '') {
            throw new InvalidArgumentException('The preset may not be empty.');
        }

        return $this->withOption('preset', $preset);
    }

    /**
     * Encode at this CRF: save() runs a plain encode instead of searching, and sampleEncode() tests it.
     * svt-av1 accepts quarter steps, e.g. 30.25.
     */
    public function withCRF(int|float $crf): static
    {
        if ($crf < 0 || $crf > 70) {
            throw new InvalidArgumentException("The CRF must be between 0 and 70, not [{$crf}].");
        }

        return $this->withOption('crf', $crf);
    }

    /**
     * The VMAF score (0-100) to reach, instead of an XPSNR score.
     */
    public function withMinVMAF(float $vmaf): static
    {
        if ($vmaf < 0 || $vmaf > 100) {
            throw new InvalidArgumentException("The VMAF score must be between 0 and 100, not [{$vmaf}].");
        }

        return $this->withOption('min-xpsnr', null)->withOption('min-vmaf', $vmaf);
    }

    /**
     * The XPSNR score to reach, instead of a VMAF score.
     */
    public function withMinXPSNR(float $xpsnr): static
    {
        return $this->withOption('min-vmaf', null)->withOption('min-xpsnr', $xpsnr);
    }

    /**
     * Fail the search when the encode would be larger than this percentage of the input.
     */
    public function withMaxEncodedPercent(int $percent): static
    {
        if ($percent < 1) {
            throw new InvalidArgumentException("The maximum encoded percentage must be positive, not [{$percent}].");
        }

        return $this->withOption('max-encoded-percent', $percent);
    }

    /**
     * How many samples to encode while searching.
     */
    public function withSamples(int $samples): static
    {
        if ($samples < 1) {
            throw new InvalidArgumentException("The number of samples must be positive, not [{$samples}].");
        }

        return $this->withOption('samples', $samples);
    }

    /**
     * ffmpeg's encoder, e.g. "libsvtav1" (ab-av1's default) or "av1_vaapi".
     */
    public function withEncoder(string $encoder): static
    {
        return $this->withOption('encoder', $encoder);
    }

    /**
     * Encoder arguments as key=value, e.g. "svtav1-params=tune=0", each passed as --enc.
     */
    public function withEncoderArgs(string ...$arguments): static
    {
        return $this->appendOption('enc', array_values($arguments));
    }

    /**
     * ffmpeg input options for the encodes, each passed as --enc-input, e.g. ['hwaccel' => 'vaapi']
     * or "hwaccel=vaapi hwaccel_output_format=vaapi".
     *
     * @param  array<string, string>|string  $options
     */
    public function withFFmpegOptions(array|string $options): static
    {
        $options = is_array($options)
            ? array_map(fn (string $key, string $value): string => "{$key}={$value}", array_keys($options), $options)
            : (preg_split('/\s+/', trim($options), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return $this->appendOption('enc-input', $options);
    }

    public function withPixelFormat(PixelFormat|string $format): static
    {
        return $this->withOption('pix-format', $format instanceof PixelFormat ? $format->value : $format);
    }

    /**
     * Filter the video before encoding and measuring, e.g. Scale::to(height: 720), passed as --vfilter.
     *
     * @throws InvalidArgumentException
     */
    public function addFilter(Filter ...$filters): static
    {
        foreach ($filters as $filter) {
            if ($filter->type() !== FilterType::Video) {
                throw new InvalidArgumentException("ab-av1 only filters video, not [{$filter}].");
            }

            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * A raw ffmpeg video filter chain, e.g. "scale=1280:-2".
     */
    public function withVideoFilter(string $filter): static
    {
        return $this->addFilter(Custom::video($filter));
    }

    /**
     * Decode the finished encode and fail on decode errors or a duration mismatch with the input.
     * Requires ab-av1 v0.11.7 or later.
     */
    public function withVerify(bool $enabled = true): static
    {
        return $this->withOption('verify', $enabled);
    }

    /**
     * Stop the final encode at the first error ffmpeg reports. Requires ab-av1 v0.11.7 or later.
     */
    public function withFailFast(bool $enabled = true): static
    {
        return $this->withOption('fail-fast', $enabled);
    }

    /**
     * Pass any ab-av1 option by its name without dashes, e.g. withOption('keyint', '10s').
     * True passes a flag, and null or false removes the option.
     */
    public function withOption(string $name, string|int|float|bool|null $value): static
    {
        $name = ltrim($name, '-');

        if ($value === null || $value === false) {
            unset($this->options[$name]);

            return $this;
        }

        $this->options[$name] = match (true) {
            $value === true => true,
            is_float($value) => Number::format($value, 4),
            default => (string) $value,
        };

        return $this;
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $options
     */
    public function withOptions(array $options): static
    {
        foreach ($options as $name => $value) {
            $this->withOption($name, $value);
        }

        return $this;
    }

    /**
     * The disk to save to. Defaults to the disk the media was opened from.
     */
    public function toDisk(Disk|Filesystem|string $disk): static
    {
        $this->targetDisk = Disk::make($disk);

        return $this;
    }

    public function withVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * The maximum seconds ab-av1 may run, instead of the configured ab-av1.timeout.
     * Keep it at or below the queue job's $timeout, so the job can handle the failure.
     */
    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * The disk the encode is saved to.
     */
    public function disk(): Disk
    {
        return $this->targetDisk ?? $this->opener->disk();
    }

    public function media(): Opener
    {
        return $this->opener;
    }

    /**
     * Encode the (first) opened file and save it to the target disk: at the CRF that reaches the
     * quality target (auto-encode), or at the CRF set with withCRF() (encode).
     *
     * @throws ProcessFailedException
     */
    public function save(string $path): AbAv1Result
    {
        $this->runBeforeSavingCallbacks();

        $startedAt = hrtime(true);

        try {
            $result = $this->export($this->saveCommand(), $path);
        } catch (Throwable $exception) {
            Event::dispatch(new ExportFailed($exception, $this->context));

            throw $exception;
        }

        $this->runAfterSavingCallbacks($result);

        if ($result->export !== null) {
            Event::dispatch(new ExportCompleted($result->export, $this->context, (hrtime(true) - $startedAt) / 1e9));
        }

        return $result;
    }

    /**
     * Find the CRF that reaches the quality target, without encoding the whole file.
     *
     * @throws ProcessFailedException
     */
    public function crfSearch(): AbAv1Result
    {
        return $this->execute(AbAv1Command::CrfSearch);
    }

    /**
     * Encode samples at the CRF set with withCRF(), to preview their quality and predicted size.
     *
     * @throws AbAv1Exception
     * @throws ProcessFailedException
     */
    public function sampleEncode(): AbAv1Result
    {
        return $this->execute(AbAv1Command::SampleEncode);
    }

    /**
     * The VMAF score of the second opened file, compared to the first.
     *
     * @throws AbAv1Exception
     * @throws ProcessFailedException
     */
    public function vmaf(): AbAv1Result
    {
        return $this->execute(AbAv1Command::Vmaf);
    }

    /**
     * The XPSNR score of the second opened file, compared to the first.
     *
     * @throws AbAv1Exception
     * @throws ProcessFailedException
     */
    public function xpsnr(): AbAv1Result
    {
        return $this->execute(AbAv1Command::Xpsnr);
    }

    /**
     * The ab-av1 arguments for the command.
     *
     * @return list<string>
     *
     * @throws AbAv1Exception
     */
    public function arguments(AbAv1Command $command, ?string $output = null, ?string $temporaryDirectory = null): array
    {
        if (in_array($command, [AbAv1Command::SampleEncode, AbAv1Command::Encode], true) && ! isset($this->options['crf'])) {
            throw AbAv1Exception::crfRequired($command);
        }

        $options = array_filter(
            $this->options,
            fn (string $name): bool => in_array($command, self::COMMAND_OPTIONS[$name] ?? AbAv1Command::cases(), true),
            ARRAY_FILTER_USE_KEY,
        );

        if ($this->filters !== [] && ! $command->compares()) {
            $options['vfilter'] = implode(',', array_map(strval(...), $this->filters));
        }

        if ($temporaryDirectory !== null && $command->encodesSamples()) {
            $options['temp-dir'] = $temporaryDirectory;
        }

        if ($output !== null && in_array($command, [AbAv1Command::AutoEncode, AbAv1Command::Encode], true)) {
            $options['output'] = $output;
        }

        $arguments = [$command->value, ...$this->inputArguments($command)];

        foreach ($options as $name => $value) {
            foreach ($value === true ? [null] : (array) $value as $item) {
                $arguments = [...$arguments, "--{$name}", ...($item !== null ? [$item] : [])];
            }
        }

        return $arguments;
    }

    /**
     * The full command line, without running it. Defaults to the command save() runs.
     */
    public function command(?AbAv1Command $command = null, ?string $output = null): string
    {
        return $this->runner->commandLine(AbAv1Executable::AbAv1, $this->arguments($command ?? $this->saveCommand(), $output));
    }

    /**
     * Encode into a temporary directory and move or upload the file to the target disk.
     */
    protected function export(AbAv1Command $command, string $path): AbAv1Result
    {
        $directory = $this->directories->create($this->opener->mediaFor()->size());
        $file = $directory->path($path);

        try {
            $directory->makeDirectory(dirname($path));

            $output = $this->run($command, $file);

            if (! is_file($file)) {
                throw AbAv1Exception::missingOutput($path);
            }

            $target = $this->disk();

            $written = $this->exporter->export($directory->path(), $target, visibility: $this->visibility, move: true);
        } finally {
            $directory->delete();
        }

        return AbAv1Result::fromOutput($command, $output, new ExportResult($target, $written));
    }

    protected function execute(AbAv1Command $command): AbAv1Result
    {
        return AbAv1Result::fromOutput($command, $this->run($command));
    }

    /**
     * Run ab-av1 and return its error output and output, in which it reports its progress and results,
     * so they aren't logged as warnings. Its sample encodes go to a temporary directory instead of the
     * working directory.
     */
    protected function run(AbAv1Command $command, ?string $output = null): string
    {
        $samples = $command->encodesSamples() ? $this->directories->create() : null;
        $reportsProgress = $output !== null && $this->reportsProgress();
        $duration = $reportsProgress ? $this->opener->probe()->duration() : null;

        try {
            $result = $this->runner->run(
                AbAv1Executable::AbAv1,
                $this->arguments($command, $output, $samples?->path()),
                timeout: $this->timeout ?? Config::integer('ab-av1.timeout', 14400),
                environment: $this->environment(),
                onErrorOutput: $reportsProgress ? $this->progressHandler(new AbAv1ProgressParser($duration)) : null,
                logWarnings: false,
            );
        } finally {
            $samples?->delete();
        }

        if ($reportsProgress) {
            $this->report(new Progress(seconds: (float) $duration, duration: $duration, finished: true));
        }

        return trim($result->errorOutput."\n".$result->output);
    }

    /**
     * @return callable(string): void
     */
    protected function progressHandler(AbAv1ProgressParser $parser): callable
    {
        return function (string $output) use ($parser): void {
            foreach ($parser->feed($output) as $progress) {
                if (! $this->report($progress)) {
                    throw ProcessCancelledException::make();
                }
            }
        };
    }

    protected function report(Progress $progress): bool
    {
        Event::dispatch(new ProgressReported($progress, $this->context));

        return $this->reportProgress($progress);
    }

    /**
     * @return list<string>
     *
     * @throws AbAv1Exception
     */
    protected function inputArguments(AbAv1Command $command): array
    {
        if (! $command->compares()) {
            return ['--input', $this->opener->mediaFor()->localPath()];
        }

        $media = $this->opener->media();

        if (count($media) < 2) {
            throw AbAv1Exception::comparisonNeedsTwoFiles($command);
        }

        return ['--reference', $media[0]->localPath(), '--distorted', $media[1]->localPath()];
    }

    /**
     * ab-av1 runs ffmpeg and ffprobe from the PATH, so put the ones laravel-media is configured
     * with in front of it.
     *
     * @return array<string, string>
     */
    protected function environment(): array
    {
        $directories = array_values(array_unique(array_filter(
            array_map(fn (Executable $executable): string => dirname($this->executables->path($executable)), [Executable::FFMpeg, Executable::FFProbe]),
            fn (string $directory): bool => $directory !== '.',
        )));

        if ($directories === []) {
            return [];
        }

        $path = getenv('PATH');

        return ['PATH' => implode(PATH_SEPARATOR, [...$directories, ...(is_string($path) && $path !== '' ? [$path] : [])])];
    }

    protected function saveCommand(): AbAv1Command
    {
        return isset($this->options['crf']) ? AbAv1Command::Encode : AbAv1Command::AutoEncode;
    }

    /**
     * @param  list<string>  $values
     */
    protected function appendOption(string $name, array $values): static
    {
        $existing = $this->options[$name] ?? [];

        $this->options[$name] = [...(is_array($existing) ? $existing : []), ...array_values(array_filter($values, fn (string $value): bool => $value !== ''))];

        if ($this->options[$name] === []) {
            unset($this->options[$name]);
        }

        return $this;
    }

    /**
     * Apply the defaults from config/ab-av1.php. Values from .env arrive as strings.
     */
    protected function applyDefaults(): void
    {
        $this->whenConfigured('preset', fn (string $preset): static => $this->withPreset(is_numeric($preset) ? (int) $preset : $preset));
        $this->whenConfigured('min_vmaf', fn (string $vmaf): static => $this->withMinVMAF((float) $vmaf));
        $this->whenConfigured('max_encoded_percent', fn (string $percent): static => $this->withMaxEncodedPercent((int) $percent));
        $this->whenConfigured('samples', fn (string $samples): static => $this->withSamples((int) $samples));
        $this->whenConfigured('encoder', fn (string $encoder): static => $this->withEncoder($encoder));
        $this->whenConfigured('encoder_args', fn (string $arguments): static => $this->withEncoderArgs(...(preg_split('/\s+/', $arguments) ?: [])));
        $this->whenConfigured('pix_format', fn (string $format): static => $this->withPixelFormat($format));
        $this->whenConfigured('video_filter', fn (string $filter): static => $this->withVideoFilter($filter));
        $this->whenConfigured('ffmpeg_input_options', fn (string $options): static => $this->withFFmpegOptions($options));
    }

    /**
     * @param  callable(string): mixed  $apply
     */
    protected function whenConfigured(string $key, callable $apply): void
    {
        $value = Config::get("ab-av1.{$key}");

        if (is_int($value) || is_float($value) || (is_string($value) && trim($value) !== '')) {
            $apply(trim((string) $value));
        }
    }
}
