---
section: Reference
order: 2
---

# Upgrading from 2.x

3.0 is built on [foxws/laravel-media](https://github.com/foxws/laravel-media). The package now only adds ab-av1: opening media, disks, temporary files, running processes, uploads, events and fakes come from laravel-media.

## Requirements

- Laravel 13 (12 is no longer supported), PHP 8.4.
- `foxws/laravel-media` ^0.3.1 is installed with the package.

## Opening media

The `AbAv1` facade, `MediaOpener` and `Exporter` are gone. Open media with laravel-media and call `abAv1()`, then pass the path to `save()`:

```php
// 2.x
$encoder = AbAv1::fromDisk('media')->open('videos/clip.mp4');

try {
    $encoder->withMinVMAF(95)->export()->toDisk('s3')->toPath('encoded/clip.mp4')->save();
} finally {
    $encoder->cleanupTemporaryFiles();
}

// 3.0
Media::fromDisk('media')->open('videos/clip.mp4')->abAv1()->withMinVMAF(95)->toDisk('s3')->save('encoded/clip.mp4');
```

- `cleanupTemporaryFiles()` is no longer needed: laravel-media deletes temporary files after every queue job and request.
- `save()` returns an `AbAv1Result` instead of `true`. `afterSaving()` callbacks receive the builder and the `AbAv1Result`.
- `withInput()`, `withOutput()`, `path()` and `fromDisk()` on the encoder are gone. Open local files through a local disk.

## Commands

| 2.x | 3.0 |
| --- | --- |
| `autoEncode()` | `save($path)` |
| `encode()` | `withCRF($crf)->save($path)` |
| `crfSearch()`, `sampleEncode()` | unchanged |
| `vmaf($reference, $distorted)` | `open($reference, $distorted)->abAv1()->vmaf()` |
| `xpsnr($reference, $distorted)` | `open($reference, $distorted)->abAv1()->xpsnr()` |
| `getBuilder()`, `jsonOutput()` | `command()` returns the command line |

The quality and encoder methods keep their names: `withPreset()`, `withCRF()`, `withMinVMAF()`, `withMinXPSNR()`, `withMaxEncodedPercent()`, `withSamples()`, `withEncoder()`, `withEncoderArgs()`, `withPixelFormat()`, `withVideoFilter()`, `withFFmpegOptions()`, `withVerify()`, `withFailFast()`, `withOption()` and `withOptions()`. `withVFrames()`, `withVerbosity()` and `withEncoders()` are removed; use `withOption()` when you need them. `setTimeout()` is now `timeout()`.

## Results

`EncodingResult` is replaced by `AbAv1Result`, with public properties:

| 2.x | 3.0 |
| --- | --- |
| `getCRFUsed()` | `crf` |
| `getVMAFScore()` / `getXPSNRScore()` | `vmaf` / `xpsnr` |
| `getEstimatedSize()` | `predictedSize` |
| `getEstimatedTime()` | `predictedTime` |
| `getOutputPath()` | `path()`, on the target disk |
| `getRawOutput()` | `output` |

## Events and exceptions

- `EncodingStarted`, `EncodingCompleted` and `EncodingFailed` are removed. Listen to laravel-media's `ProcessStarted`, `ProcessCompleted` and `ProcessFailed` (every command), or `ExportCompleted` and `ExportFailed` (`save()`, with `withContext()`).
- ab-av1 failures throw laravel-media's `ProcessFailedException`, with `isRetryable()` and report context, instead of `EncodingException`. A missing binary throws laravel-media's `ExecutableNotFoundException`.
- Other errors throw `Foxws\AbAv1\Exceptions\AbAv1Exception`.

## Configuration

- Removed: `log_channel`, `temporary_files_root`, `cache_files_root` (use `media.log_channel` and `media.temporary_files`), `force_generic_input` (commands no longer run through a shell, so file names can't break them), `vframes` and `verbosity`.
- `php artisan ab-av1:info` is replaced by `php artisan media:info`, which lists ab-av1.

## Tests

Replace `Process::fake()` with `FakeAbAv1::respond(Media::fake())`. See [Testing](testing.md).
