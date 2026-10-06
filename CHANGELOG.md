# Changelog

All notable changes to `laravel-ab-av1` will be documented in this file.

## 3.1.0 - 2026-10-06

### What's Changed

* Report encode progress and stop logging ab-av1's reports as warnings by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/13
* Use laravel-media's Number and faked error output by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/14

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/3.0.0...3.1.0

## 3.0.0 - 2026-10-06

### What's Changed

* Build on laravel-media (3.0) by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/12

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/2.0.1...3.0.0

## 2.0.1 - 2026-10-02

2.0.0 added `withVerify()` and `withFailFast()`, but `MediaOpener` didn't declare them, so PHPStan reported them as undefined in apps using the package. This release declares them, and adds `withInput()` to the facade.

### What's Changed

* Raise PHPStan to level 8 by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/11
* Declare withVerify(), withFailFast() and withInput() for static analysis by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/10

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/2.0.0...2.0.1

## 2.0.0 - 2026-10-02

### Breaking

* `EncodingResult::getCRFUsed()` now returns `?float` instead of `?int`, and `setCRFUsed()` takes a `float`. ab-av1 v0.11 with svt-av1 v4 searches CRF in quarter steps (e.g. `30.25`), which the package couldn't read before.

If you don't use the CRF value as an `int`, you can upgrade without changes.

### What's Changed

* Fix quarter-step CRF parsing and crfSearch, and add docs by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/9
  * `getCRFUsed()` and `getVMAFScore()` returned `null` for quarter-step CRF results.
  * `crfSearch()` ran a full `auto-encode` instead of `crf-search`.
  * Commands without an output path crashed with a `TypeError`.
  * ab-av1's sample files went to the working directory; they now go to a temporary directory.
  * Config values set in `.env` (e.g. `AB_AV1_PRESET=4`) threw exceptions.
  * `withCRF()` accepts floats up to 70, and `withPreset()` svt-av1 presets up to 13.
  * New `withVerify()` and `withFailFast()`, for ab-av1 v0.11.7.
  * All package exceptions now extend `RuntimeException`.
  * PHPStan level 7, a `docs/` folder, a rewritten README, and a Laravel Boost skill.
  

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/1.1.0...2.0.0

## 1.1.0 - 2026-09-26

### What's Changed

* Update the default AV1 preset (4 → 6) and minimum VMAF (90 → 94) for better quality by @francoism90 in https://github.com/foxws/laravel-ab-av1/commit/b262f7f
* build(deps): Bump actions/checkout from 6 to 7 by @dependabot[bot] in https://github.com/foxws/laravel-ab-av1/pull/7
* Document forwarded methods for static analysis by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/8

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/1.0.1...1.1.0

## 1.0.1 - 2026-04-26

### What's Changed

* build(deps): Bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 by @dependabot[bot] in https://github.com/foxws/laravel-ab-av1/pull/5
* build(deps): Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/foxws/laravel-ab-av1/pull/6

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/1.0.0...1.0.1

## 1.0.0 - 2026-03-18

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/v1.0.0...1.0.0

## v1.0.0 - 2026-03-18

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.8.0...v1.0.0

## 0.8.0 - 2026-03-17

### What's Changed

* build(deps): Bump ramsey/composer-install from 3 to 4 by @dependabot[bot] in https://github.com/foxws/laravel-ab-av1/pull/4

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/foxws/laravel-ab-av1/pull/4

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.7.0...0.8.0

## 0.7.0 - 2026-02-28

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.6.0...0.7.0

## 0.6.0 - 2026-02-28

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.5.0...0.6.0

## 0.5.0 - 2026-02-28

### What's Changed

* feat: Add force_generic_input configuration and improve media handling by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/3

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.4.0...0.5.0

## 0.4.0 - 2026-02-21

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.3.0...0.4.0

## 0.3.0 - 2026-02-17

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.2.0...0.3.0

## 0.2.0 - 2026-02-17

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/compare/0.1.0...0.2.0

## 0.1.0 - 2026-02-17

### What's Changed

* Initial commit by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/1
* feat: Add ab-av1 encoding support  by @francoism90 in https://github.com/foxws/laravel-ab-av1/pull/2

### New Contributors

* @francoism90 made their first contribution in https://github.com/foxws/laravel-ab-av1/pull/1

**Full Changelog**: https://github.com/foxws/laravel-ab-av1/commits/0.1.0
