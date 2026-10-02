---
section: Usage
order: 1
---

# Usage

## Encode a file on a disk

Open a video on any Laravel disk, choose a quality target, and save the result to any disk:

```php
use Foxws\AbAv1\Facades\AbAv1;

$encoder = AbAv1::fromDisk('media')->open('videos/clip.mp4');

try {
    $encoder
        ->withPreset(6)
        ->withMinVMAF(95)
        ->export()
        ->toDisk('s3')
        ->toPath('encoded/clip.mp4')
        ->afterSaving(fn () => logger('Encoded clip.mp4'))
        ->save();
} finally {
    $encoder->cleanupTemporaryFiles();
}
```

`save()` runs ab-av1's `auto-encode`: it test-encodes samples to find the CRF that reaches your VMAF target, encodes the whole video with it, and uploads the result. Files on remote disks like S3 are downloaded to a temporary folder first, so always call `cleanupTemporaryFiles()` when you're done.

`withPreset()` and `withMinVMAF()` are optional here when you set defaults in the [config file](configuration.md).

## Encode a local file

For a file that's already on the server, pass its path and read the result:

```php
$result = AbAv1::withInput('/path/to/video.mp4')
    ->withPreset(6)
    ->withMinVMAF(95)
    ->withOutput('/path/to/output.mp4')
    ->autoEncode();

$result->getCRFUsed();   // e.g. 30.25
$result->getVMAFScore(); // e.g. 95.1
```

## Choose the quality

| Method | What it does |
| --- | --- |
| `withMinVMAF(95)` | The VMAF score to reach, from 0 to 100. Around 95 is very hard to tell apart from the original. |
| `withMinXPSNR(40)` | Use XPSNR as the quality measure instead of VMAF. |
| `withMaxEncodedPercent(80)` | Fail when the result would be larger than this percentage of the original. |
| `withPreset(6)` | Encoder speed. For svt-av1, from 0 (slowest, smallest files) to 13 (fastest). |
| `withSamples(8)` and `withVFrames(240)` | How many samples to test, and how many frames each. More is more accurate, but slower. |

## Other commands

Each of these runs one ab-av1 command and returns an `EncodingResult`:

| Method | ab-av1 command | Use it to |
| --- | --- | --- |
| `autoEncode()` | `auto-encode` | Find the right CRF and encode the whole video. |
| `crfSearch()` | `crf-search` | Only find the right CRF, without encoding. |
| `sampleEncode()` | `sample-encode` | Test one CRF on a few samples, to preview quality and size. Needs `withCRF()`. |
| `encode()` | `encode` | Encode the whole video with a CRF you choose. Needs `withCRF()`. |
| `vmaf($original, $encoded)` | `vmaf` | Compare two files and get their VMAF score. |
| `xpsnr($original, $encoded)` | `xpsnr` | Compare two files and get their XPSNR score. |

CRF values can have quarter steps with svt-av1, for example `withCRF(30.25)`. Lower means better quality and larger files.

## Use another encoder

ab-av1 uses svt-av1 (`libsvtav1`) by default. To use a hardware encoder, like VAAPI on Intel and AMD graphics, pass FFmpeg's encoder name:

```php
AbAv1::fromDisk('media')
    ->open('videos/clip.mp4')
    ->withEncoder('av1_vaapi')
    ->withMinVMAF(95)
    ->export()
    ->toDisk('media')
    ->toPath('encoded/clip.mp4')
    ->save();
```

To pass extra FFmpeg input options, use `withFFmpegOptions()`, for example `->withFFmpegOptions(['hwaccel' => 'vaapi'])`. For encoder arguments, use `withEncoderArgs()`.

## Check the result

Encodes can fail without FFmpeg noticing, for example on a damaged input. With ab-av1 v0.11.7 or later, you can catch that:

```php
$encoder->withVerify()->withFailFast();
```

- `withVerify()` decodes the finished encode, and fails on decode errors or when its length doesn't match the input.
- `withFailFast()` stops the encode at the first error FFmpeg reports.

## Results

An `EncodingResult` has:

| Method | Returns |
| --- | --- |
| `getCRFUsed()` | The CRF that was chosen or used, as a float, e.g. `30.25`. |
| `getVMAFScore()` / `getXPSNRScore()` | The quality score. |
| `getEstimatedSize()` | The predicted size in bytes. |
| `getEstimatedTime()` | The predicted encoding time in seconds. |
| `getOutputPath()` | Where the encoded file was written. |
| `getRawOutput()` | ab-av1's full output, for debugging. |

## Errors

Every exception the package throws extends `Foxws\AbAv1\Exceptions\EncodingException` or another class in `Foxws\AbAv1\Exceptions`, and all of them extend PHP's `RuntimeException`:

- `MediaNotFoundException`: the input file doesn't exist.
- `InvalidEncodingConfigurationException`: a required option is missing, like a preset or a quality target.
- `ExecutableNotFoundException`: ab-av1 or FFmpeg can't be found.
- `EncodingException`: ab-av1 itself failed. The message includes its error output.

Invalid values, like a preset of 20, throw an `InvalidArgumentException` straight away.
