<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Commands;

use Composer\InstalledVersions;
use Foxws\AbAv1\Exceptions\ExecutableNotFoundException;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class AbAv1Command extends Command
{
    protected $signature = 'ab-av1:info';

    protected $description = 'Display package information and verify ab-av1 installation';

    public function handle(): int
    {
        info('Laravel ab-av1 - Information & Verification');

        $binary = Config::get('ab-av1.binary', 'ab-av1');
        $tempDir = Config::get('ab-av1.temporary_files_root', storage_path('app/ab-av1/temp'));
        $cacheDir = Config::get('ab-av1.cache_files_root');
        $timeout = Config::get('ab-av1.timeout', 14400);
        $preset = Config::get('ab-av1.preset', 4);
        $minVmaf = Config::get('ab-av1.min_vmaf', 90);
        $maxEncodedPercent = Config::get('ab-av1.max_encoded_percent', 200);
        $logChannel = Config::get('ab-av1.log_channel');
        $logStatus = $logChannel === false ? 'Disabled' : ($logChannel ?: Config::get('logging.default', 'Default'));

        $encoderInitialized = false;
        try {
            Encoder::create();
            $encoderInitialized = true;
        } catch (ExecutableNotFoundException $e) {
            error('✗ Cannot initialize Encoder: '.$e->getMessage());
        } catch (Throwable $e) {
            error('✗ Error initializing Encoder: '.$e->getMessage());
        }

        $rows = [
            ['Package Version', InstalledVersions::getPrettyVersion('foxws/laravel-ab-av1') ?? 'dev-main', '✓'],
            ['ab-av1 Binary', $binary, $encoderInitialized ? '✓' : '✗'],
            ['Timeout', "{$timeout}s", '✓'],
            ['Default Preset', (string) $preset, '✓'],
            ['Min VMAF', (string) $minVmaf, '✓'],
            ['Max Encoded %', "{$maxEncodedPercent}%", '✓'],
            ['Temp Directory', $tempDir, $this->getTempDirStatus($tempDir)],
            ['Cache Directory', $cacheDir ?: 'Not configured', $cacheDir ? $this->getTempDirStatus($cacheDir) : '⚠'],
            ['Logging', $logStatus, '✓'],
            ['Force Generic Input', Config::get('ab-av1.force_generic_input') ? 'Enabled' : 'Disabled', '✓'],
        ];

        if ($vframes = Config::get('ab-av1.vframes')) {
            $rows[] = ['Video Frames', (string) $vframes, '✓'];
        }

        if ($samples = Config::get('ab-av1.samples')) {
            $rows[] = ['Samples', (string) $samples, '✓'];
        }

        if ($encoderArgs = Config::get('ab-av1.encoder_args')) {
            $rows[] = ['Encoder Args', $encoderArgs, '✓'];
        }

        if ($pixFormat = Config::get('ab-av1.pix_format')) {
            $rows[] = ['Pixel Format', $pixFormat, '✓'];
        }

        if ($videoFilter = Config::get('ab-av1.video_filter')) {
            $rows[] = ['Video Filter', $videoFilter, '✓'];
        }

        if ($verbosity = Config::get('ab-av1.verbosity')) {
            $rows[] = ['Verbosity', (string) $verbosity, '✓'];
        }

        if ($ffmpegOptions = Config::get('ab-av1.ffmpeg_input_options')) {
            $rows[] = ['FFmpeg Input Options', is_array($ffmpegOptions) ? implode(' ', $ffmpegOptions) : $ffmpegOptions, '✓'];
        }

        table(['Setting', 'Value', 'Status'], $rows);

        if (! is_writable($tempDir) && is_dir($tempDir)) {
            error("✗ Temporary directory is not writable: {$tempDir}");

            return self::FAILURE;
        }

        if (! is_dir($tempDir)) {
            warning('⚠ Temporary directory does not exist (will be created automatically)');
        }

        if (! $encoderInitialized) {
            error('✗ ab-av1 is not properly configured. Please check the errors above.');

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

        return is_writable($tempDir) ? '✓' : '✗';
    }
}
