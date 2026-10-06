<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Builder;
use Foxws\AbAv1\AbAv1Command;
use Foxws\AbAv1\AbAv1Executable;
use Foxws\AbAv1\AbAv1Result;
use Foxws\AbAv1\Exceptions\AbAv1Exception;
use Foxws\AbAv1\Testing\FakeAbAv1;
use Foxws\Media\Encoding\PixelFormat;
use Foxws\Media\Events\ExportCompleted;
use Foxws\Media\Events\ExportFailed;
use Foxws\Media\Events\ProgressReported;
use Foxws\Media\Exceptions\FailureReason;
use Foxws\Media\Exceptions\ProcessFailedException;
use Foxws\Media\Executables\Executables;
use Foxws\Media\Facades\Media;
use Foxws\Media\Filters\Custom;
use Foxws\Media\Filters\Scale;
use Foxws\Media\Filters\Volume;
use Foxws\Media\Opener;
use Foxws\Media\Process\Progress;
use Foxws\Media\Process\Runner;
use Foxws\Media\Testing\MediaFake;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Psr\Log\AbstractLogger;

beforeEach(function (): void {
    Storage::fake('media');
    Storage::fake('encoded');
    Storage::disk('media')->put('videos/clip.mp4', 'source');
    Storage::disk('media')->put('videos/encoded.mp4', 'encoded');

    $this->fake = FakeAbAv1::respond(Media::fake(), crf: 30.25, score: 95.1);
});

function abAv1(string ...$paths): AbAv1Builder
{
    return Media::fromDisk('media')->open(...($paths !== [] ? $paths : ['videos/clip.mp4']))->abAv1();
}

/**
 * @return list<string>
 */
function lastAbAv1Run(MediaFake $fake): array
{
    $commands = $fake->commands(AbAv1Executable::AbAv1);

    return end($commands) ?: [];
}

it('auto-encodes to the target quality and saves the encode to the target disk', function (): void {
    $result = abAv1()->toDisk('encoded')->save('av1/clip.mp4');

    $arguments = lastAbAv1Run($this->fake);

    expect($arguments[0])->toBe('auto-encode')
        ->and(optionValue($arguments, '--input'))->toEndWith('videos/clip.mp4')
        ->and(optionValue($arguments, '--preset'))->toBe('6')
        ->and(optionValue($arguments, '--min-vmaf'))->toBe('94')
        ->and(optionValue($arguments, '--max-encoded-percent'))->toBe('300')
        ->and(optionValue($arguments, '--temp-dir'))->not->toBeNull()
        ->and(optionValue($arguments, '--output'))->toEndWith('av1/clip.mp4')
        ->and($result->command)->toBe(AbAv1Command::AutoEncode)
        ->and($result->crf)->toBe(30.25)
        ->and($result->vmaf)->toBe(95.1)
        ->and($result->path())->toBe('av1/clip.mp4')
        ->and($result->export?->disk()->name())->toBe('encoded');

    Storage::disk('encoded')->assertExists('av1/clip.mp4');
});

it('saves to the disk the media was opened from by default', function (): void {
    abAv1()->save('av1/clip.mp4');

    Storage::disk('media')->assertExists('av1/clip.mp4');
});

it('saves with the visibility', function (): void {
    abAv1()->withVisibility('private')->save('av1/clip.mp4');

    expect(Storage::disk('media')->getVisibility('av1/clip.mp4'))->toBe('private');
})->skipOnWindows();

it('encodes at a fixed CRF without searching when one is set', function (): void {
    abAv1()->withCRF(32.5)->save('av1/clip.mp4');

    $arguments = lastAbAv1Run($this->fake);

    expect($arguments[0])->toBe('encode')
        ->and(optionValue($arguments, '--crf'))->toBe('32.5')
        ->and($arguments)->not->toContain('--min-vmaf', '--max-encoded-percent', '--temp-dir', '--samples');
});

it('searches the CRF without encoding the whole file', function (): void {
    $result = abAv1()->withMinVMAF(93)->withSamples(8)->crfSearch();

    $arguments = lastAbAv1Run($this->fake);

    expect($arguments[0])->toBe('crf-search')
        ->and(optionValue($arguments, '--min-vmaf'))->toBe('93')
        ->and(optionValue($arguments, '--samples'))->toBe('8')
        ->and($arguments)->not->toContain('--output')
        ->and($result->crf)->toBe(30.25)
        ->and($result->predictedPercent)->toBe(45.0)
        ->and($result->export)->toBeNull();
});

it('targets XPSNR instead of VMAF', function (): void {
    $result = abAv1()->withMinXPSNR(40)->crfSearch();

    $arguments = lastAbAv1Run($this->fake);

    expect(optionValue($arguments, '--min-xpsnr'))->toBe('40')
        ->and($arguments)->not->toContain('--min-vmaf')
        ->and($result->xpsnr)->toBe(95.1)
        ->and($result->vmaf)->toBeNull();
});

it('encodes samples at a CRF', function (): void {
    abAv1()->withCRF(30)->sampleEncode();

    $arguments = lastAbAv1Run($this->fake);

    expect($arguments[0])->toBe('sample-encode')
        ->and(optionValue($arguments, '--crf'))->toBe('30')
        ->and(optionValue($arguments, '--temp-dir'))->not->toBeNull()
        ->and($arguments)->not->toContain('--min-vmaf');
});

it('needs a CRF to encode samples', function (): void {
    abAv1()->sampleEncode();
})->throws(AbAv1Exception::class, 'needs a CRF');

it('compares the second opened file to the first', function (string $method, AbAv1Command $command): void {
    $result = abAv1('videos/clip.mp4', 'videos/encoded.mp4')->withVideoFilter('scale=1280:-2')->{$method}();

    $arguments = lastAbAv1Run($this->fake);

    expect($arguments[0])->toBe($command->value)
        ->and(optionValue($arguments, '--reference'))->toEndWith('videos/clip.mp4')
        ->and(optionValue($arguments, '--distorted'))->toEndWith('videos/encoded.mp4')
        ->and($arguments)->not->toContain('--input', '--preset', '--min-vmaf', '--vfilter')
        ->and($result->command)->toBe($command)
        ->and($command === AbAv1Command::Vmaf ? $result->vmaf : $result->xpsnr)->toBe(95.1);
})->with([
    'vmaf' => ['vmaf', AbAv1Command::Vmaf],
    'xpsnr' => ['xpsnr', AbAv1Command::Xpsnr],
]);

it('needs two files to compare', function (): void {
    abAv1()->vmaf();
})->throws(AbAv1Exception::class, 'compares two files');

it('passes encoder settings and video filters', function (): void {
    abAv1()
        ->withEncoder('av1_vaapi')
        ->withEncoderArgs('low_power=1', 'rc_mode=CQP')
        ->withFFmpegOptions(['hwaccel' => 'vaapi'])
        ->withFFmpegOptions('hwaccel_output_format=vaapi')
        ->withPixelFormat(PixelFormat::Yuv420p10le)
        ->addFilter(Scale::to(height: 720), Custom::video('fps=30'))
        ->withVerify()
        ->withFailFast()
        ->save('av1/clip.mp4');

    $arguments = lastAbAv1Run($this->fake);
    $command = implode(' ', $arguments);

    expect(optionValue($arguments, '--encoder'))->toBe('av1_vaapi')
        ->and($command)->toContain('--enc low_power=1 --enc rc_mode=CQP')
        ->and($command)->toContain('--enc-input hwaccel=vaapi --enc-input hwaccel_output_format=vaapi')
        ->and(optionValue($arguments, '--pix-format'))->toBe('yuv420p10le')
        ->and(optionValue($arguments, '--vfilter'))->toBe('scale=-2:720,fps=30')
        ->and($arguments)->toContain('--verify', '--fail-fast');
});

it('only accepts video filters', function (): void {
    abAv1()->addFilter(Volume::times(0.5));
})->throws(InvalidArgumentException::class, 'only filters video');

it('passes any option, and removes it with null or false', function (): void {
    abAv1()
        ->withOptions(['keyint' => '10s', '--scd' => true, 'cache' => false])
        ->withOption('max-encoded-percent', null)
        ->withVerify(false)
        ->crfSearch();

    $arguments = lastAbAv1Run($this->fake);

    expect(optionValue($arguments, '--keyint'))->toBe('10s')
        ->and($arguments)->toContain('--scd')
        ->and($arguments)->not->toContain('--cache', '--max-encoded-percent', '--verify');
});

it('rejects values ab-av1 would refuse', function (Closure $configure): void {
    $configure(abAv1());
})->with([
    'preset above 13' => [fn (AbAv1Builder $builder) => $builder->withPreset(14)],
    'empty preset' => [fn (AbAv1Builder $builder) => $builder->withPreset('')],
    'CRF above 70' => [fn (AbAv1Builder $builder) => $builder->withCRF(71)],
    'VMAF above 100' => [fn (AbAv1Builder $builder) => $builder->withMinVMAF(101)],
    'no samples' => [fn (AbAv1Builder $builder) => $builder->withSamples(0)],
    'no encoded percentage' => [fn (AbAv1Builder $builder) => $builder->withMaxEncodedPercent(0)],
])->throws(InvalidArgumentException::class);

it('applies the configured defaults, which arrive from .env as strings', function (): void {
    config([
        'ab-av1.preset' => '4',
        'ab-av1.min_vmaf' => '95.5',
        'ab-av1.max_encoded_percent' => null,
        'ab-av1.samples' => '6',
        'ab-av1.encoder' => 'libsvtav1',
        'ab-av1.encoder_args' => 'tune=0 film-grain=8',
        'ab-av1.pix_format' => 'yuv420p10le',
        'ab-av1.video_filter' => 'scale=1280:-2',
        'ab-av1.ffmpeg_input_options' => 'hwaccel=vaapi  hwaccel_output_format=vaapi',
    ]);

    abAv1()->crfSearch();

    $arguments = lastAbAv1Run($this->fake);
    $command = implode(' ', $arguments);

    expect(optionValue($arguments, '--preset'))->toBe('4')
        ->and(optionValue($arguments, '--min-vmaf'))->toBe('95.5')
        ->and(optionValue($arguments, '--samples'))->toBe('6')
        ->and(optionValue($arguments, '--encoder'))->toBe('libsvtav1')
        ->and($command)->toContain('--enc tune=0 --enc film-grain=8')
        ->and($command)->toContain('--enc-input hwaccel=vaapi --enc-input hwaccel_output_format=vaapi')
        ->and(optionValue($arguments, '--pix-format'))->toBe('yuv420p10le')
        ->and(optionValue($arguments, '--vfilter'))->toBe('scale=1280:-2')
        ->and($arguments)->not->toContain('--max-encoded-percent');
});

it('runs the saving callbacks and dispatches the export events with context', function (): void {
    Event::fake([ExportCompleted::class, ExportFailed::class]);

    $saved = null;

    abAv1()
        ->withContext(['video_id' => 1])
        ->beforeSaving(fn (AbAv1Builder $builder) => $builder->withSamples(4))
        ->afterSaving(function (AbAv1Builder $builder, AbAv1Result $result) use (&$saved): void {
            $saved = $result->path();
        })
        ->save('av1/clip.mp4');

    expect($saved)->toBe('av1/clip.mp4')
        ->and(optionValue(lastAbAv1Run($this->fake), '--samples'))->toBe('4');

    Event::assertDispatched(ExportCompleted::class, fn (ExportCompleted $event): bool => $event->context === ['video_id' => 1]
        && $event->result->path() === 'av1/clip.mp4');
    Event::assertNotDispatched(ExportFailed::class);
});

it('throws and dispatches ExportFailed when ab-av1 fails, without saving', function (): void {
    Event::fake([ExportCompleted::class, ExportFailed::class]);

    $this->fake->failNext(AbAv1Executable::AbAv1, 'Error: ffmpeg encode exit code 1');

    expect(fn () => abAv1()->withContext(['video_id' => 1])->save('av1/clip.mp4'))
        ->toThrow(ProcessFailedException::class, 'ab-av1 exited with code 1');

    Storage::disk('media')->assertMissing('av1/clip.mp4');
    Event::assertDispatched(ExportFailed::class, fn (ExportFailed $event): bool => $event->context === ['video_id' => 1]);
    Event::assertNotDispatched(ExportCompleted::class);
});

it('reports the progress of the encode and dispatches it with context', function (): void {
    Event::fake([ProgressReported::class]);
    FakeAbAv1::respond($this->fake, errorOutput: implode("\n", [
        '[2026-10-06T12:00:00Z INFO  ab_av1::command::sample_encode] encoding sample 1/4 crf 30',
        '[2026-10-06T12:00:16Z INFO  ab_av1::command::encode] 25%, 24.5 fps, eta 45 seconds',
        '[2026-10-06T12:00:32Z INFO  ab_av1::command::encode] 50%, 25 fps, eta 30 seconds',
        '',
    ]));
    $updates = [];

    abAv1()
        ->withContext(['video_id' => 1])
        ->onProgress(function (Progress $progress) use (&$updates): void {
            $updates[] = $progress;
        })
        ->save('av1/clip.mp4');

    expect(array_map(fn (Progress $progress): ?float => $progress->percentage(), $updates))->toBe([25.0, 50.0, 100.0])
        ->and($updates[0]->fps)->toBe(24.5)
        ->and($updates[1]->remaining())->toBe(30.0)
        ->and($updates[2]->finished)->toBeTrue();

    Event::assertDispatchedTimes(ProgressReported::class, 3);
    Event::assertDispatched(ProgressReported::class, fn (ProgressReported $event): bool => $event->context === ['video_id' => 1]);
});

it('cancels the encode when a progress callback returns false', function (): void {
    FakeAbAv1::respond($this->fake, errorOutput: "[2026-10-06T12:00:16Z INFO  ab_av1::command::encode] 25%, 24.5 fps, eta 45 seconds\n");

    expect(fn () => abAv1()->onProgress(fn (): bool => false)->save('av1/clip.mp4'))
        ->toThrow(fn (ProcessFailedException $exception) => expect($exception->reason)->toBe(FailureReason::Cancelled));

    Storage::disk('media')->assertMissing('av1/clip.mp4');
});

it('does not log what ab-av1 reports on its error output as warnings', function (): void {
    $logger = new class extends AbstractLogger
    {
        /** @var list<mixed> */
        public array $levels = [];

        public function log($level, Stringable|string $message, array $context = []): void
        {
            $this->levels[] = $level;
        }
    };
    FakeAbAv1::respond($this->fake, errorOutput: "[2026-10-06T12:00:00Z INFO  ab_av1::command::crf_search] crf 30 successful\n");
    (fn () => $this->logger = $logger)->call(app(Runner::class));

    abAv1()->save('av1/clip.mp4');

    expect($logger->levels)->not->toBeEmpty()->not->toContain('warning');
});

it('fails when ab-av1 finishes without writing the encode', function (): void {
    $this->fake->respondUsing(AbAv1Executable::AbAv1, fn (array $arguments): string => '');

    abAv1()->save('av1/clip.mp4');
})->throws(AbAv1Exception::class, 'without writing av1/clip.mp4');

it('shows the command line without running it', function (): void {
    expect(abAv1()->command())->toStartWith('ab-av1 auto-encode --input ')
        ->and(abAv1()->withCRF(30)->command(AbAv1Command::SampleEncode))->toStartWith('ab-av1 sample-encode ');

    $this->fake->assertNotRan(AbAv1Executable::AbAv1);
});

it('runs ab-av1 with the configured ffmpeg and ffprobe first in its PATH', function (): void {
    $directory = sys_get_temp_dir().'/laravel-ab-av1-bin-'.getmypid();
    @mkdir($directory, 0777, true);

    foreach (['ffmpeg', 'ffprobe', 'ab-av1'] as $name) {
        file_put_contents("{$directory}/{$name}", "#!/bin/sh\nexit 0\n");
        chmod("{$directory}/{$name}", 0755);
    }

    config([
        'media.executables.ffmpeg' => "{$directory}/ffmpeg",
        'media.executables.ffprobe' => "{$directory}/ffprobe",
        'ab-av1.binary' => "{$directory}/ab-av1",
    ]);

    app()->forgetInstance(Executables::class);
    app()->forgetInstance(Runner::class);

    Process::fake(['*' => Process::result(output: "crf 28 VMAF 95.2 predicted video stream size 1 GiB (40%) taking 2 hours\n")]);

    $result = app(Opener::class)->fromDisk('media')->open('videos/clip.mp4')->abAv1()->crfSearch();

    expect($result->crf)->toBe(28.0)
        ->and($result->predictedSize)->toBe(1024 ** 3)
        ->and($result->predictedTime)->toBe(7200.0);

    Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command)
        && $process->command[0] === "{$directory}/ab-av1"
        && str_starts_with($process->environment['PATH'] ?? '', $directory.PATH_SEPARATOR));
})->skipOnWindows();
