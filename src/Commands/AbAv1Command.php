<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\ExecutableFinder;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class AbAv1Command extends Command
{
    protected $signature = 'ab-av1:info';

    protected $description = 'Display package information and verify ab-av1 installation';

    public function handle(): int
    {
        info('🔍 Laravel ab-av1 - Information & Verification');

        // Package version
        $composerPath = base_path('vendor/foxws/laravel-ab-av1/composer.json');
        $packageVersion = 'dev-main';

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $packageVersion = $composer['version'] ?? 'dev-main';
        }

        note("Package Version: {$packageVersion}");

        // Verify ab-av1 installation
        $configuredBinary = Config::get('ab-av1.binary', 'ab-av1');

        $finder = new ExecutableFinder;
        $abAv1Binary = $finder->find($configuredBinary);
        $ffmpegBinary = $finder->find('ffmpeg');

        if ($abAv1Binary) {
            $this->components->info("✓ ab-av1 found: {$abAv1Binary}");
        } else {
            error("✗ ab-av1 not found: {$configuredBinary}");
        }

        if ($ffmpegBinary) {
            $this->components->info("✓ ffmpeg found: {$ffmpegBinary}");
        } else {
            error('✗ ffmpeg not found in PATH');
        }

        // Configuration details
        $timeout = Config::get('ab-av1.timeout', 14400);
        $preset = Config::get('ab-av1.preset', 8);
        $minVmaf = Config::get('ab-av1.min_vmaf', 95);
        $maxEncodedPercent = Config::get('ab-av1.max_encoded_percent', 200);
        $encoder = Config::get('ab-av1.encoder', 'default (ab-av1 will choose)');
        $logChannel = Config::get('ab-av1.log_channel');
        $logStatus = $logChannel === false ? 'Disabled' : ($logChannel ?: Config::get('logging.default', 'Default'));
        $tempDir = Config::get('ab-av1.temporary_files_root', storage_path('app/ab-av1/temp'));
        $cacheDir = Config::get('ab-av1.cache_files_root');

        table(
            ['Configuration', 'Value', 'Status'],
            [
                ['ab-av1 Binary', $abAv1Binary ?: 'Not found', $abAv1Binary ? '✓' : '✗'],
                ['FFmpeg Binary', $ffmpegBinary ?: 'Not found', $ffmpegBinary ? '✓' : '✗'],
                ['Timeout', "{$timeout} seconds", '✓'],
                ['Default Preset', $preset, '✓'],
                ['Min VMAF', $minVmaf, '✓'],
                ['Max Encoded %', "{$maxEncodedPercent}%", '✓'],
                ['Encoder', $encoder ?: 'default', '✓'],
                ['Temp Directory', $tempDir, $this->getTempDirStatus($tempDir)],
                ['Cache Directory', $cacheDir ?: 'Not configured', $cacheDir ? $this->getTempDirStatus($cacheDir) : '⚠'],
                ['Logging', $logStatus, '✓'],
            ]
        );

        // Optional config values
        $optionalConfigs = [];

        if ($vframes = Config::get('ab-av1.vframes')) {
            $optionalConfigs[] = ['Video Frames', $vframes, '✓'];
        }

        if ($samples = Config::get('ab-av1.samples')) {
            $optionalConfigs[] = ['Samples', $samples, '✓'];
        }

        if ($encoderArgs = Config::get('ab-av1.encoder_args')) {
            $optionalConfigs[] = ['Encoder Args', $encoderArgs, '✓'];
        }

        if ($pixFormat = Config::get('ab-av1.pix_format')) {
            $optionalConfigs[] = ['Pixel Format', $pixFormat, '✓'];
        }

        if ($videoFilter = Config::get('ab-av1.video_filter')) {
            $optionalConfigs[] = ['Video Filter', $videoFilter, '✓'];
        }

        if ($verbosity = Config::get('ab-av1.verbosity')) {
            $optionalConfigs[] = ['Verbosity', $verbosity, '✓'];
        }

        if (! empty(Config::get('ab-av1.ffmpeg_input_options', []))) {
            $hwAccel = Config::get('ab-av1.ffmpeg_input_options.hwaccel', 'Not set');
            $optionalConfigs[] = ['HW Acceleration', $hwAccel, '✓'];
        }

        if (! empty($optionalConfigs)) {
            $this->newLine();
            info('📝 Optional Configuration');
            table(
                ['Setting', 'Value', 'Status'],
                $optionalConfigs
            );
        }

        // Check temporary directory
        if (! is_dir($tempDir)) {
            warning('⚠ Temporary directory does not exist (will be created automatically)');
        } elseif (! is_writable($tempDir)) {
            error("✗ Temporary directory is not writable: {$tempDir}");

            return self::FAILURE;
        }

        // Check cache directory if configured
        if ($cacheDir && is_dir($cacheDir) && ! is_writable($cacheDir)) {
            error("✗ Cache directory is not writable: {$cacheDir}");

            return self::FAILURE;
        }

        if (! $abAv1Binary || ! $ffmpegBinary) {
            error('✗ ab-av1 and/or ffmpeg are not properly installed. Please install them first.');
            note('Installation: cargo install ab-av1');
            note('Or visit: https://github.com/alexheretic/ab-av1');

            return self::FAILURE;
        }

        info('✅ ab-av1 is properly configured and ready to use!');

        return self::SUCCESS;
    }

    protected function getTempDirStatus(string $tempDir): string
    {
        if (! is_dir($tempDir)) {
            return '⚠';
        }

        if (! is_writable($tempDir)) {
            return '✗';
        }

        return '✓';
    }
}
