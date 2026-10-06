---
section: Getting Started
order: 1
---

# Installation

## Install the package

```bash
composer require foxws/laravel-ab-av1
```

This also installs [foxws/laravel-media](https://github.com/foxws/laravel-media), which opens the media, runs ab-av1 and saves the result. Its own settings (temporary files, logging, the ffmpeg and ffprobe paths) live in `config/media.php`.

Publish the config file to change ab-av1's defaults:

```bash
php artisan vendor:publish --tag="ab-av1-config"
```

This creates `config/ab-av1.php`. See [Configuration](configuration.md) for every option.

## Install ab-av1

The package doesn't ship the binary. Install [ab-av1](https://github.com/alexheretic/ab-av1) with your package manager:

```bash
# Arch Linux
sudo pacman -S ab-av1

# macOS
brew install ab-av1

# Anywhere with Rust
cargo install ab-av1
```

ab-av1 calls FFmpeg, so FFmpeg must be installed too, built with `libsvtav1` and `libvmaf`. Most distribution packages include both. When laravel-media is configured with full paths to ffmpeg and ffprobe (`MEDIA_FFMPEG_PATH`, `MEDIA_FFPROBE_PATH`), ab-av1 runs those.

Some features need a recent ab-av1: quarter-step CRF values need v0.11 with svt-av1 v4, and `withVerify()` and `withFailFast()` need v0.11.7.

## Point to the binary

The package runs `ab-av1` from your `PATH`. To use another binary, for example one installed with `cargo`, set it in your `.env`:

```bash
AB_AV1_BINARY=/home/user/.cargo/bin/ab-av1
```

## Check the setup

```bash
php artisan media:info
```

This lists ab-av1 next to ffmpeg and ffprobe, with the path and version it found. `php artisan about` shows the same paths.

## AI agents

The package includes a [Laravel Boost](https://github.com/laravel/boost) skill. Run `php artisan boost:install` (or `boost:update`) after installing, and your AI agent learns how to encode, verify and test with it.
