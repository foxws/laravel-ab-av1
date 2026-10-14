---
title: Introduction
metadata:
  role: Media
  group: media
  eyebrow: "Video · AV1 · VMAF"
  desc: "Encode video to AV1 at the quality you choose, without guessing encoder settings."
  lead: "Tell it the quality you want, and ab-av1 finds the smallest AV1 encode that reaches it. Read from any Laravel disk, and write to any disk."
  requires: "PHP ^8.4"
  laravel: "13.x"
  runtime: "ab-av1, FFmpeg, foxws/laravel-media"
  licence: MIT
  used_by:
    - name: Stry
      desc: "A self-hosted video streaming app."
      href: "https://github.com/francoism90/stry"
---

# Introduction

This package runs [ab-av1](https://github.com/alexheretic/ab-av1) from Laravel to encode video to AV1.

Normally you pick a CRF value (a quality setting) and hope the result looks good and isn't too big. ab-av1 does that for you: you set a target quality score, called [VMAF](https://github.com/Netflix/vmaf), and it test-encodes short samples of your video to find the CRF that reaches it with the smallest file.

It's an add-on for [foxws/laravel-media](https://github.com/foxws/laravel-media): you open media with laravel-media, and `abAv1()` encodes it.

```php
use Foxws\Media\Facades\Media;

Media::fromDisk('media')
    ->open('videos/clip.mp4')
    ->abAv1()
    ->withMinVMAF(95)
    ->toDisk('s3')
    ->save('encoded/clip.mp4');
```

## Features

- Encode at a target quality (VMAF or XPSNR) instead of guessing a CRF.
- Read input from, and write output to, any Laravel disk, with laravel-media's concurrent S3 uploads.
- Use svt-av1, hardware encoders like VAAPI, or any other encoder FFmpeg supports, with laravel-media's filters.
- Test-encode samples first, or compare the quality of two files.
- laravel-media's process and export events, error reasons, temporary file cleanup after queue jobs, and `Media::fake()` in tests.

## Requirements

- PHP 8.4 or higher
- Laravel 13
- [foxws/laravel-media](https://github.com/foxws/laravel-media) 0.3 or higher
- [ab-av1](https://github.com/alexheretic/ab-av1), with FFmpeg built with svt-av1 and libvmaf

> [!TIP]
> Let your AI agent do the hard parts. This package ships a [Laravel Boost](https://laravel.com/docs/boost) skill, `laravel-ab-av1-development`, that teaches your agent how to install, configure and use Laravel ab-av1. Install Boost and select the skill when prompted:
>
> ```bash
> composer require laravel/boost --dev
> php artisan boost:install
> ```
>
> Already using Boost? Run `php artisan boost:update --discover` to pick up the skill. Then just ask, for example: *"Encode uploaded videos to AV1 at a VMAF of 95 and save them to S3."*

## Pages

- [Installation](installation.md)
- [Usage](usage.md)
- [Testing](testing.md)
- [Configuration](configuration.md)
- [Upgrading from 2.x](upgrading.md)
