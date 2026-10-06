<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1Executable;

it('runs ab-av1 from the PATH by default', function (): void {
    expect(AbAv1Executable::AbAv1->identifier())->toBe('ab-av1')
        ->and(AbAv1Executable::AbAv1->configuredPath())->toBe('ab-av1')
        ->and(AbAv1Executable::AbAv1->environmentKey())->toBe('AB_AV1_BINARY')
        ->and(AbAv1Executable::AbAv1->versionArguments())->toBe(['--version']);
});

it('uses the configured binary', function (): void {
    config(['ab-av1.binary' => '/opt/cargo/bin/ab-av1']);

    expect(AbAv1Executable::AbAv1->configuredPath())->toBe('/opt/cargo/bin/ab-av1');
});

it('falls back to the PATH when the configured binary is empty', function (): void {
    config(['ab-av1.binary' => '']);

    expect(AbAv1Executable::AbAv1->configuredPath())->toBe('ab-av1');
});
