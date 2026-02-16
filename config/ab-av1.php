<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Logging Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for ab-av1 encoding operations.
    | Default is the application's default log channel.
    |
    */
    'log_channel' => env('AB_AV1_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | The default timeout (in seconds) for encoding operations.
    | Encodings can take a very long time, so adjust based on your needs.
    |
    */
    'timeout' => env('AB_AV1_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | The default encoding preset to use.
    | Options: 0 (slowest, best quality) to 8 (fastest, lowest quality).
    |
    */
    'preset' => env('AB_AV1_PRESET', 8),

    /*
    |--------------------------------------------------------------------------
    | Default Encoders
    |--------------------------------------------------------------------------
    |
    | Default list of encoders to try (in order).
    | supports: av1_svtenc, av1_vaapi, libx264, libx265, etc.
    |
    */
    'encoders' => env('AB_AV1_ENCODERS', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Minimum VMAF
    |--------------------------------------------------------------------------
    |
    | Default VMAF quality target for auto-encode.
    | Range: 0-100, typical values 75-95
    |
    */
    'min_vmaf' => env('AB_AV1_MIN_VMAF', 95),

    /*
    |--------------------------------------------------------------------------
    | Maximum Encoded Percent
    |--------------------------------------------------------------------------
    |
    | Maximum allowed encode size as percentage of source.
    | Used to prevent oversized encodes.
    |
    */
    'max_encoded_percent' => env('AB_AV1_MAX_ENCODED_PERCENT', 150),

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Input Options
    |----------||||
    | Additional FFmpeg input options (e.g., hardware acceleration).
    | Example: ['hwaccel' => 'vaapi', 'hwaccel_output_format' => 'vaapi']
    |
    */
    'ffmpeg_input_options' => [
        // 'hwaccel' => env('AB_AV1_HWACCEL', null),
        // 'hwaccel_output_format' => env('AB_AV1_HWACCEL_OUTPUT_FORMAT', null),
    ],
];
