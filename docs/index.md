---
title: Introduction
metadata:
  role: Media
  group: media
  eyebrow: "Video · AV1 · VMAF"
  desc: "Encode video to AV1 at the quality you choose, without guessing encoder settings."
  lead: "Tell it the quality you want, and ab-av1 finds the smallest AV1 encode that reaches it. Read from any Laravel disk, and write to any disk."
  requires: "PHP ^8.4"
  laravel: "12.x / 13.x"
  runtime: "ab-av1, FFmpeg"
  licence: MIT
  used_by:
    - name: Stry
      desc: "A self-hosted video streaming app."
      href: "https://github.com/francoism90/stry"
---

# Introduction

This package runs [ab-av1](https://github.com/alexheretic/ab-av1) from Laravel to encode video to AV1.

Normally you pick a CRF value (a quality setting) and hope the result looks good and isn't too big. ab-av1 does that for you: you set a target quality score, called [VMAF](https://github.com/Netflix/vmaf), and it test-encodes short samples of your video to find the CRF that reaches it with the smallest file.

```php
use Foxws\AbAv1\Facades\AbAv1;

AbAv1::fromDisk('media')
    ->open('videos/clip.mp4')
    ->withMinVMAF(95)
    ->export()
    ->toDisk('s3')
    ->toPath('encoded/clip.mp4')
    ->save();
```

## Features

- Encode at a target quality (VMAF or XPSNR) instead of guessing a CRF.
- Read input from, and write output to, any Laravel disk: local, S3 or your own.
- Use svt-av1, hardware encoders like VAAPI, or any other encoder FFmpeg supports.
- Test-encode a sample first, or compare the quality of two files.
- Listen to events when an encode starts, finishes or fails.

## Requirements

- PHP 8.4 or higher
- Laravel 12 or 13
- [ab-av1](https://github.com/alexheretic/ab-av1), with FFmpeg built with svt-av1 and libvmaf

## Pages

- [Installation](installation.md)
- [Usage](usage.md)
- [Events](events.md)
- [Configuration](configuration.md)
