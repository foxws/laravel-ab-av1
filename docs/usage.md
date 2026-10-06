---
section: Usage
order: 1
---

# Usage

## Encode a file on a disk

Open a video on any Laravel disk with laravel-media, choose a quality target, and save the result to any disk:

```php
use Foxws\AbAv1\AbAv1Builder;
use Foxws\AbAv1\AbAv1Result;
use Foxws\Media\Facades\Media;

$result = Media::fromDisk('media')
    ->open('videos/clip.mp4')
    ->abAv1()
    ->withPreset(6)
    ->withMinVMAF(95)
    ->toDisk('s3')
    ->withVisibility('private')
    ->afterSaving(fn (AbAv1Builder $builder, AbAv1Result $result) => logger("Encoded at CRF {$result->crf}"))
    ->save('encoded/clip.mp4');

$result->crf;    // e.g. 30.25
$result->vmaf;   // e.g. 95.1
$result->path(); // "encoded/clip.mp4"
```

`save()` runs ab-av1's `auto-encode`: it test-encodes samples to find the CRF that reaches your VMAF target, encodes the whole video with it, and moves or uploads the result. Without `toDisk()`, it's saved to the disk the video was opened from.

`withPreset()` and `withMinVMAF()` are optional when you set defaults in the [config file](configuration.md).

Files on remote disks like S3 are downloaded to laravel-media's temporary directory first. laravel-media deletes its temporary files after every queue job and at the end of each request, so there's nothing to clean up yourself.

## Encode at a fixed CRF

With `withCRF()`, `save()` runs a plain `encode` at that CRF instead of searching:

```php
Media::fromDisk('media')->open('videos/clip.mp4')->abAv1()->withCRF(30)->save('encoded/clip.mp4');
```

CRF values can have quarter steps with svt-av1, for example `withCRF(30.25)`. Lower means better quality and larger files.

## Choose the quality

| Method | What it does |
| --- | --- |
| `withMinVMAF(95)` | The VMAF score to reach, from 0 to 100. Around 95 is very hard to tell apart from the original. |
| `withMinXPSNR(40)` | Use XPSNR as the quality measure instead of VMAF. |
| `withMaxEncodedPercent(80)` | Fail when the result would be larger than this percentage of the original. |
| `withPreset(6)` | Encoder speed. For svt-av1, from 0 (slowest, smallest files) to 13 (fastest). |
| `withSamples(8)` | How many samples to test. More is more accurate, but slower. |

## Other commands

| Method | ab-av1 command | Use it to |
| --- | --- | --- |
| `save($path)` | `auto-encode`, or `encode` after `withCRF()` | Encode the whole video and save it. |
| `crfSearch()` | `crf-search` | Only find the right CRF, without encoding. |
| `sampleEncode()` | `sample-encode` | Test one CRF on a few samples, to preview quality and size. Needs `withCRF()`. |
| `vmaf()` | `vmaf` | Get the VMAF score of the second opened file, compared to the first. |
| `xpsnr()` | `xpsnr` | Get the XPSNR score of the second opened file, compared to the first. |

Each returns an `AbAv1Result`. To compare two files, open the original first:

```php
$score = Media::fromDisk('media')
    ->open('videos/clip.mp4', 'encoded/clip.mp4')
    ->abAv1()
    ->vmaf()
    ->vmaf; // e.g. 95.4
```

Every command only gets the options it accepts, so config defaults like the preset never break `vmaf()`.

## Use another encoder

ab-av1 uses svt-av1 (`libsvtav1`) by default. To use a hardware encoder, like VAAPI on Intel and AMD graphics, pass FFmpeg's encoder name and its input options:

```php
Media::fromDisk('media')
    ->open('videos/clip.mp4')
    ->abAv1()
    ->withEncoder('av1_vaapi')
    ->withFFmpegOptions(['hwaccel' => 'vaapi', 'hwaccel_output_format' => 'vaapi'])
    ->withMinVMAF(95)
    ->save('encoded/clip.mp4');
```

`withEncoderArgs('svtav1-params=tune=0')` passes encoder arguments (`--enc`), and `withPixelFormat(PixelFormat::Yuv420p10le)` sets the pixel format. Any other ab-av1 option goes through `withOption('keyint', '10s')`; `true` passes a flag, and `null` removes an option.

## Filters

Video filters from laravel-media are applied before encoding and measuring, passed as `--vfilter`:

```php
use Foxws\Media\Filters\Custom;
use Foxws\Media\Filters\Scale;

$builder->addFilter(Scale::to(height: 720), Custom::video('fps=30'));
```

`withVideoFilter('scale=1280:-2')` takes a raw filter chain. ab-av1 only filters video, so audio filters throw an `InvalidArgumentException`.

## Check the result

Encodes can fail without FFmpeg noticing, for example on a damaged input. With ab-av1 v0.11.7 or later, you can catch that:

```php
$builder->withVerify()->withFailFast();
```

- `withVerify()` decodes the finished encode, and fails on decode errors or when its length doesn't match the input.
- `withFailFast()` stops the encode at the first error FFmpeg reports.

## Results

An `AbAv1Result` has:

| Property or method | Holds |
| --- | --- |
| `crf` | The CRF that was chosen or used, e.g. `30.25`. |
| `vmaf` / `xpsnr` | The quality score. |
| `predictedSize` | The predicted video stream size in bytes. |
| `predictedPercent` | The predicted size as a percentage of the input. |
| `predictedTime` | The predicted encoding time in seconds. |
| `export` / `path()` | For `save()`: laravel-media's `ExportResult`, and the saved path. |
| `output` | ab-av1's full output, for debugging. |

## Queues

Encodes are slow, often longer than the video itself without hardware encoding, so run them in a queued job. `timeout()` overrides the `ab-av1.timeout` config for one encode; keep it below the job's `$timeout`:

```php
use Foxws\Media\Exceptions\ProcessFailedException;

public function handle(): void
{
    try {
        Media::fromDisk('media')
            ->open($this->video->path)
            ->abAv1()
            ->timeout(7000)
            ->withContext(['video_id' => $this->video->id])
            ->save("encoded/{$this->video->id}.mp4");
    } catch (ProcessFailedException $exception) {
        $exception->isRetryable() ? $this->release(60) : $this->fail($exception);
    }
}
```

`withContext()` is passed to laravel-media's `ExportCompleted` and `ExportFailed` events, which `save()` dispatches. The runner also dispatches `ProcessStarted`, `ProcessCompleted` and `ProcessFailed` for every command. ab-av1 doesn't report progress in a form the runner can read, so `onProgress()` isn't available.

## Errors

- `Foxws\Media\Exceptions\ProcessFailedException`: ab-av1 failed or timed out. `context()` has the command, exit code and error output for error trackers, and `isRetryable()` tells whether a retry may help.
- `Foxws\Media\Exceptions\ExecutableNotFoundException`: ab-av1, FFmpeg or ffprobe can't be found.
- `Foxws\Media\Exceptions\MediaNotFoundException`: nothing was opened, or the input can't be read.
- `Foxws\AbAv1\Exceptions\AbAv1Exception`: `sampleEncode()` without a CRF, a comparison with one file, or an encode that wrote no file.

Invalid values, like a preset of 20, throw an `InvalidArgumentException` straight away.
