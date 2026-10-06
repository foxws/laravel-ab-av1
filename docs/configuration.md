---
section: Reference
order: 1
---

# Configuration

Publish the config file to change the defaults:

```bash
php artisan vendor:publish --tag="ab-av1-config"
```

Every option can also be set in your `.env`. The quality and encoder options are defaults: calling a method like `withPreset()` overrides them for that encode.

Temporary files, logging, and the ffmpeg and ffprobe paths ab-av1 runs come from laravel-media's `config/media.php`.

## ab-av1

| Option | `.env` | Default | What it does |
| --- | --- | --- | --- |
| `binary` | `AB_AV1_BINARY` | `ab-av1` | The ab-av1 binary to run, from your `PATH` or a full path. |
| `timeout` | `AB_AV1_TIMEOUT` | `14400` | How long one command may run, in seconds. Encoding a long video can take hours. |

## Quality and encoder defaults

| Option | `.env` | Default | What it does |
| --- | --- | --- | --- |
| `preset` | `AB_AV1_PRESET` | `6` | Encoder speed. For svt-av1, from 0 (slowest, smallest files) to 13 (fastest). |
| `min_vmaf` | `AB_AV1_MIN_VMAF` | `94` | The VMAF score `auto-encode` and `crf-search` aim for. |
| `max_encoded_percent` | `AB_AV1_MAX_ENCODED_PERCENT` | `300` | Fail when the result would be larger than this percentage of the original. |
| `samples` | `AB_AV1_SAMPLES` | ab-av1's default | How many samples to test. |
| `encoder` | `AB_AV1_ENCODER` | ab-av1's default (`libsvtav1`) | FFmpeg's encoder name, e.g. `av1_vaapi`. |
| `encoder_args` | `AB_AV1_ENCODER_ARGS` | none | Space-separated `key=value` encoder arguments, each passed as `--enc`. |
| `pix_format` | `AB_AV1_PIX_FORMAT` | ab-av1's default | The pixel format, e.g. `yuv420p10le`. |
| `video_filter` | `AB_AV1_VIDEO_FILTER` | none | An FFmpeg video filter, e.g. `scale=1280:-2`. |
| `ffmpeg_input_options` | `AB_AV1_FFMPEG_INPUT_OPTIONS` | none | Space-separated FFmpeg input options, e.g. `hwaccel=vaapi hwaccel_output_format=vaapi`, each passed as `--enc-input`. |
