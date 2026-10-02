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

## ab-av1

| Option | `.env` | Default | What it does |
| --- | --- | --- | --- |
| `binary` | `AB_AV1_BINARY` | `ab-av1` | The ab-av1 binary to run, from your `PATH` or a full path. |
| `timeout` | `AB_AV1_TIMEOUT` | `14400` | How long one command may run, in seconds. Encoding a long video can take hours. |
| `log_channel` | `AB_AV1_LOG_CHANNEL` | your `LOG_CHANNEL` | Where to log ab-av1's commands and output. Set to `false` to turn logging off. |

## Quality and encoder defaults

| Option | `.env` | Default | What it does |
| --- | --- | --- | --- |
| `preset` | `AB_AV1_PRESET` | `6` | Encoder speed. For svt-av1, from 0 (slowest, smallest files) to 13 (fastest). |
| `min_vmaf` | `AB_AV1_MIN_VMAF` | `94` | The VMAF score `auto-encode` and `crf-search` aim for. |
| `max_encoded_percent` | `AB_AV1_MAX_ENCODED_PERCENT` | `300` | Fail when the result would be larger than this percentage of the original. |
| `samples` | `AB_AV1_SAMPLES` | ab-av1's default | How many samples to test. |
| `vframes` | `AB_AV1_VFRAMES` | ab-av1's default | How many frames each sample has. |
| `encoder` | `AB_AV1_ENCODER` | ab-av1's default (`libsvtav1`) | FFmpeg's encoder name, e.g. `av1_vaapi`. |
| `encoder_args` | `AB_AV1_ENCODER_ARGS` | none | Extra arguments for the encoder. |
| `pix_format` | `AB_AV1_PIX_FORMAT` | ab-av1's default | The pixel format, e.g. `yuv420p10le`. |
| `video_filter` | `AB_AV1_VIDEO_FILTER` | none | An FFmpeg video filter, e.g. `scale=1280:-2`. |
| `ffmpeg_input_options` | `AB_AV1_FFMPEG_INPUT_OPTIONS` | none | FFmpeg input options, e.g. `hwaccel=vaapi hwaccel_output_format=vaapi`. |
| `verbosity` | `AB_AV1_VERBOSITY` | `0` | How much ab-av1 logs: `0`, `1` or `2`. |

## Files

| Option | `.env` | Default | What it does |
| --- | --- | --- | --- |
| `temporary_files_root` | `AB_AV1_TEMPORARY_FILES_ROOT` | `storage/app/ab-av1/temp` | Where files from remote disks are downloaded, ab-av1 keeps its sample encodes, and encodes are written before upload. |
| `cache_files_root` | `AB_AV1_CACHE_FILES_ROOT` | `/dev/shm` | Not used yet. ab-av1's sample files go to `temporary_files_root`. |
| `force_generic_input` | `AB_AV1_FORCE_GENERIC_INPUT` | `true` | Pass ab-av1 a generic copy of the input path, so special characters in file names can't break the command. |
