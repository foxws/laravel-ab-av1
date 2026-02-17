# Laravel ab-av1 Implementation Guidance

## Overview
Laravel ab-av1 is a Laravel wrapper for the ab-av1 AV1 encoding tool (https://github.com/alexheretic/ab-av1).
It provides a fluent PHP interface for encoding videos to AV1 format with VMAF quality targeting.

## Key Commands
- `auto-encode`: Automatically determine best CRF for target VMAF quality
- `crf-search`: Binary search to find optimal CRF value
- `sample-encode`: Quick sample encoding for testing
- `encode`: Full video encoding with specified CRF

## Design Pattern
Model Laravel Streamer architecture but for ab-av1 (NOT Shaka):
1. Main `Encoder` class that wraps ab-av1 CLI invocations
2. `CommandBuilder` to construct ab-av1 CLI arguments
3. Events for encoding started/completed/failed
4. Exceptions for error handling
5. Facade for easy access
6. Service Provider for bootstrapping

## Key Differences from Streamer
- ab-av1 is a single-file encoder (processes one input at a time)
- Returns VMAF scores and encoding metrics (not packaging into manifests)
- Supports encoders: av1_svtenc, av1_vaapi, libx264, libx265, etc.
- Uses --preset (ultrafast, superfast, veryfast, faster, fast, medium, slow, slower)
- CRF values typically 0-63 (lower = better quality)

## Integration with CreateNewVideoStream
Consider this will work like:
- Create Encoder instance
- Configure encoding options (preset, encoders, etc.)
- Execute encoding
- Handle results/errors

## Priority Features
1. Basic `Encoder` with `auto-encode` support
2. Configuration management
3. Event dispatching
4. Error handling
