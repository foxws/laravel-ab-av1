<?php

/**
 * Laravel ab-av1 Package - Basic Usage Examples
 *
 * This file demonstrates common usage patterns for the Laravel ab-av1 package.
 */

use Foxws\AbAv1\Facades\AbAv1;

/**
 * Example 1: Auto-encode with VMAF targeting
 *
 * Automatically determines the best CRF value to achieve target VMAF quality
 */
function example_auto_encode(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withPreset('medium')
        ->withMinVMAF(95)
        ->withEncoders(['av1_svtenc', 'av1_vaapi'])
        ->withOutput('/path/to/output.mp4');

    try {
        $result = $encoder->autoEncode();

        echo "VMAF Score: {$result->getVMAFScore()}".PHP_EOL;
        echo "CRF Used: {$result->getCRFUsed()}".PHP_EOL;
        echo "Estimated Size: {$result->getEstimatedSize()} bytes".PHP_EOL;
        echo "Estimated Time: {$result->getEstimatedTime()} seconds".PHP_EOL;
    } catch (\Exception $e) {
        echo "Encoding failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 2: Direct encode with specified CRF
 *
 * Encode with a specific CRF value (no quality search)
 */
function example_encode_with_crf(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withCRF(30)
        ->withPreset('slow')
        ->withEncoder('av1_svtenc')
        ->withOutput('/path/to/output.mp4');

    try {
        $result = $encoder->encode();

        echo 'Encoding completed!'.PHP_EOL;
        echo "Output: {$result->getOutputPath()}".PHP_EOL;
    } catch (\Exception $e) {
        echo "Encoding failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 3: CRF search to find optimal value
 *
 * Binary search to find the best CRF for target VMAF
 */
function example_crf_search(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withPreset('medium')
        ->withMinVMAF(95)
        ->withMaxEncodedPercent(200);

    try {
        $result = $encoder->crfSearch();

        echo 'Search Result:'.PHP_EOL;
        echo "  CRF: {$result->getCRFUsed()}".PHP_EOL;
        echo "  VMAF: {$result->getVMAFScore()}".PHP_EOL;
    } catch (\Exception $e) {
        echo "CRF search failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 4: Sample encoding for quick testing
 *
 * Encode a sample to quickly estimate quality before full encode
 */
function example_sample_encode(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withCRF(25)
        ->withPreset('medium');

    try {
        $result = $encoder->sampleEncode();

        echo 'Sample Encode Results:'.PHP_EOL;
        echo "  VMAF Score: {$result->getVMAFScore()}".PHP_EOL;
        echo '  Predicted Size: '.round($result->getEstimatedSize() / 1024 / 1024, 2).' MB'.PHP_EOL;
        echo '  Predicted Time: '.round($result->getEstimatedTime() / 60, 2).' minutes'.PHP_EOL;
    } catch (\Exception $e) {
        echo "Sample encoding failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 5: Using configuration defaults
 *
 * Apply default configuration from config/ab-av1.php
 */
function example_using_defaults(): void
{
    // Requires config to be set up with defaults
    $encoder = app(\Foxws\AbAv1\AbAv1::class)->withDefaults()
        ->withInput('/path/to/video.mp4')
        ->withOutput('/path/to/output.mp4');

    try {
        $result = $encoder->autoEncode();

        echo 'Encoding with defaults completed!'.PHP_EOL;
    } catch (\Exception $e) {
        echo "Failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 6: Hardware acceleration
 *
 * Enable hardware acceleration for faster encoding
 */
function example_hardware_acceleration(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withPreset('medium')
        ->withMinVMAF(95)
        ->withFFmpegOptions([
            'hwaccel' => 'vaapi',
            'hwaccel_output_format' => 'vaapi',
        ]);

    try {
        $result = $encoder->autoEncode();

        echo 'Hardware-accelerated encoding completed!'.PHP_EOL;
    } catch (\Exception $e) {
        echo "Failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 7: Comparing VMAF scores
 *
 * Calculate VMAF between original and encoded file
 */
function example_vmaf_comparison(): void
{
    $encoder = AbAv1::encode();

    try {
        $result = $encoder->vmaf(
            referenceFile: '/path/to/original.mp4',
            distortedFile: '/path/to/encoded.mp4'
        );

        echo 'VMAF Comparison:'.PHP_EOL;
        echo "  VMAF Score: {$result->getVMAFScore()}".PHP_EOL;
    } catch (\Exception $e) {
        echo "VMAF calculation failed: {$e->getMessage()}".PHP_EOL;
    }
}

/**
 * Example 8: Listening to encoding events
 *
 * Listen for encoding events to track progress
 */
function example_event_listening(): void
{
    // In a service provider or listener
    \Foxws\AbAv1\Events\EncodingStarted::listen(function (\Foxws\AbAv1\Events\EncodingStarted $event) {
        echo "Encoding started: {$event->inputPath}".PHP_EOL;
    });

    \Foxws\AbAv1\Events\EncodingCompleted::listen(function (\Foxws\AbAv1\Events\EncodingCompleted $event) {
        echo "Encoding completed! VMAF: {$event->result->getVMAFScore()}".PHP_EOL;
    });

    \Foxws\AbAv1\Events\EncodingFailed::listen(function (\Foxws\AbAv1\Events\EncodingFailed $event) {
        echo "Encoding failed: {$event->exception->getMessage()}".PHP_EOL;
    });
}

/**
 * Example 9: Integration with Video model (like in CreateNewVideoStream)
 *
 * Encoding a video within domain logic
 */
function example_domain_integration(): void
{
    // In Domain/Videos/Actions/CreateNewVideoEncode.php
    // $video is a Domain\Videos\Models\Video instance

    $video = null; // Assume this is passed in

    if (! $video || ! $video->hasMedia('source')) {
        return;
    }

    $sourceMedia = $video->getFirstMedia('source');
    $sourcePath = $sourceMedia->getPath();

    $encoder = AbAv1::encode()
        ->withInput($sourcePath)
        ->withPreset('medium')
        ->withMinVMAF(95)
        ->withOutput(storage_path("videos/{$video->id}/encoded.mp4"));

    try {
        $result = $encoder->autoEncode();

        // Store result in database
        $video->update([
            'encoded_path' => $result->getOutputPath(),
            'vmaf_score' => $result->getVMAFScore(),
            'crf_used' => $result->getCRFUsed(),
        ]);

        // Dispatch event
        \Illuminate\Support\Facades\Event::dispatch(new \App\Events\VideoEncoded($video, $result));
    } catch (\Exception $e) {
        $video->markAsFailed();

        throw $e;
    }
}
/**
 * Example 9: Hardware encoder with encoder args
 *
 * Use Intel QuickSync (av1_qsv) with custom encoder parameters
 */
function example_quicksync_with_args(): void
{
    $encoder = AbAv1::encode()
        ->withInput('/path/to/video.mp4')
        ->withOutput('/path/to/output.mp4')
        ->withPreset('slow')  // You can use string or numeric (0-8)
        ->withEncoder('av1_qsz')
        // FFmpeg input options (hardware acceleration)
        ->withFFmpegOptions('hwaccel=qsv qsv_device=/dev/dri/renderD128')
        // Encoder-specific options via --enc flag (colon-separated parameters)
        ->withEncoderArgs('av1_qsz_params=preset=slow:lookahead=1:lookahead_depth=60:extbrc=1')
        ->withMinVMAF(95);

    try {
        $result = $encoder->autoEncode();

        echo 'QuickSync encoding completed!'.PHP_EOL;
        echo "VMAF Score: {$result->getVMAFScore()}".PHP_EOL;
    } catch (\Exception $e) {
        echo "Failed: {$e->getMessage()}".PHP_EOL;
    }
}
