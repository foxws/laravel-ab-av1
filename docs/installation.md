---
section: Getting Started
order: 1
---

# Installation

## Install the package

```bash
composer require foxws/laravel-ab-av1
```

Publish the config file:

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

ab-av1 calls FFmpeg, so FFmpeg must be installed too, built with `libsvtav1` and `libvmaf`. Most distribution packages include both.

Some features need a recent ab-av1: quarter-step CRF values need v0.11 with svt-av1 v4, and `withVerify()` and `withFailFast()` need v0.11.7.

## Point to the binary

The package runs `ab-av1` from your `PATH`. To use another binary, for example one installed with `cargo`, set it in your `.env`:

```bash
AB_AV1_BINARY=/home/user/.cargo/bin/ab-av1
```

## Check the setup

```bash
php artisan ab-av1:info
```

This shows the ab-av1 version it found, and the settings it will use.
