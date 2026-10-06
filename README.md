# Laravel ab-av1

[![Latest Version on Packagist](https://img.shields.io/packagist/v/foxws/laravel-ab-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-ab-av1)
[![GitHub Tests Action Status](https://github.com/foxws/laravel-ab-av1/actions/workflows/tests.yml/badge.svg)](https://github.com/foxws/laravel-ab-av1/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/foxws/laravel-ab-av1/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/foxws/laravel-ab-av1/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/foxws/laravel-ab-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-ab-av1)

Runs [ab-av1](https://github.com/alexheretic/ab-av1) from Laravel to encode video to AV1. You set the quality you want as a [VMAF](https://github.com/Netflix/vmaf) score, and ab-av1 finds the smallest encode that reaches it. It's an add-on for [foxws/laravel-media](https://github.com/foxws/laravel-media): read the source from any Laravel disk, and write the result to any disk.

See the [full documentation](docs): [Installation](docs/installation.md), [Usage](docs/usage.md), [Testing](docs/testing.md), [Configuration](docs/configuration.md), [Upgrading from 2.x](docs/upgrading.md).

## Requirements

- PHP 8.4 or higher
- Laravel 13
- [foxws/laravel-media](https://github.com/foxws/laravel-media) 0.3 or higher (installed with the package)
- [ab-av1](https://github.com/alexheretic/ab-av1), with FFmpeg built with svt-av1 and libvmaf

## Installation

```bash
composer require foxws/laravel-ab-av1
```

```bash
php artisan vendor:publish --tag="ab-av1-config"
```

Install the `ab-av1` binary, then check the setup:

```bash
php artisan media:info
```

See [Installation](docs/installation.md) for details.

## Quick start

```php
use Foxws\Media\Facades\Media;

$result = Media::fromDisk('media')
    ->open('videos/clip.mp4')
    ->abAv1()
    ->withPreset(6)
    ->withMinVMAF(95)
    ->toDisk('s3')
    ->save('encoded/clip.mp4');

$result->crf;  // e.g. 30.25
$result->vmaf; // e.g. 95.1
```

This finds the CRF that reaches a VMAF score of 95, encodes the video with it, and uploads the result to `encoded/clip.mp4` on the `s3` disk. laravel-media cleans up the temporary files after the queue job or request.

See [Usage](docs/usage.md) for the other ab-av1 commands, hardware encoders, filters and checking the result.

## Testing

```bash
composer test
```

## Links

- [CHANGELOG](CHANGELOG.md)
- [Security policy](../../security/policy)
- [ab-av1](https://github.com/alexheretic/ab-av1)
- [Laravel Media](https://github.com/foxws/laravel-media), to stream or package the result as HLS and DASH

## Credits

- [francoism90](https://github.com/francoism90)
- [All Contributors](../../contributors)
- [ab-av1](https://github.com/alexheretic/ab-av1) by Alex Butler, which does the actual work

Used by [Stry](https://github.com/francoism90/stry), a self-hosted video streaming app.

AI, specifically [Claude](https://claude.com/product/claude-code), was used to help build this package. All AI-assisted output is reviewed by me, and I retain final say over everything that is implemented and released.

## License

MIT. See [License File](LICENSE.md).
