# Laravel ab-av1

A Laravel wrapper for [ab-av1](https://github.com/alexheretic/ab-av1), the AV1 video encoder with automatic CRF calculation and VMAF quality targeting.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/foxws/laravel-ab-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-ab-av1)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/foxws/laravel-ab-av1/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/foxws/laravel-ab-av1/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/foxws/laravel-ab-av1/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/foxws/laravel-ab-av1/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/foxws/laravel-ab-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-ab-av1)

## Features

- **Automatic CRF Discovery**: Find the optimal CRF value to achieve target VMAF quality
- **Multiple Encoders**: Support for av1_svtenc, av1_vaapi, libx264, libx265, and other ffmpeg encoders
- **Quick Sampling**: Test encode quality before full encoding
- **Hardware Acceleration**: Support for VAAPI and other hardware acceleration methods
- **VMAF/XPSNR Comparison**: Calculate quality scores between original and encoded files
- **Event Dispatching**: Listen to encoding events (started, completed, failed)
- **Fluent Interface**: Chainable configuration API

## Installation

Ensure `ab-av1` is installed:

```bash
# macOS
brew install ab-av1

# Linux (Arch)
sudo pacman -S ab-av1
```

Install the Laravel package:

```bash
composer require foxws/laravel-ab-av1
```

Publish the config:

```bash
php artisan vendor:publish --provider="Foxws\AbAv1\AbAv1ServiceProvider"
```

## Configuration

### Binary Path

By default, the package expects `ab-av1` to be in your system PATH. If you need to use a specific binary path or version, configure it in `config/ab-av1.php` or via environment variable:

```bash
# .env
AB_AV1_BINARY=/usr/local/bin/ab-av1
# Or use a specific cargo installation
AB_AV1_BINARY=/home/user/.cargo/bin/ab-av1
```

Verify your installation:

```bash
php artisan ab-av1:info
```

## Quick Start

```php
use Foxws\AbAv1\Facades\AbAv1;

$result = AbAv1::encode()
    ->withInput('/path/to/video.mp4')
    ->withPreset('medium')
    ->withMinVMAF(95)
    ->autoEncode();

echo "VMAF: {$result->getVMAFScore()}";
echo "CRF: {$result->getCRFUsed()}";
```

## Usage Examples

### Auto-encode (Recommended)

Automatically find the best CRF for your target VMAF quality:

```php
$result = AbAv1::encode()
    ->withInput('video.mp4')
    ->withPreset('medium')
    ->withMinVMAF(95)
    ->withOutput('output.mp4')
    ->autoEncode();
```

### Direct Encode

Encode with a specific CRF value:

```php
$result = AbAv1::encode()
    ->withInput('video.mp4')
    ->withCRF(30)
    ->withPreset('slow')
    ->encode();
```

### Sample Encode

Quick test to preview quality:

```php
$result = AbAv1::encode()
    ->withInput('video.mp4')
    ->withCRF(28)
    ->sampleEncode();
```

### Hardware Acceleration

Enable VAAPI for faster encoding:

```php
$result = AbAv1::encode()
    ->withInput('video.mp4')
    ->withPreset('medium')
    ->withMinVMAF(95)
    ->withFFmpegOptions([
        'hwaccel' => 'vaapi',
        'hwaccel_output_format' => 'vaapi',
    ])
    ->autoEncode();
```

## Testing

```bash
composer test
```

## Documentation

See [examples/basic-usage.php](examples/basic-usage.php) for comprehensive usage examples.

For detailed documentation, see the [generated docs](DOCUMENTATION_INDEX.md).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [francoism90](https://github.com/foxws)
- [All Contributors](../../contributors)
- Inspired by [ab-av1](https://github.com/alexheretic/ab-av1)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
