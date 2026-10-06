<?php

declare(strict_types=1);

use Foxws\AbAv1\AbAv1ProgressParser;

it('reads the progress ab-av1 logs while it encodes the whole file', function (): void {
    $updates = new AbAv1ProgressParser(duration: 120.0)->feed(implode("\n", [
        '[2026-10-06T12:00:00Z INFO  ab_av1::command::sample_encode] encoding sample 1/4 crf 30',
        '[2026-10-06T12:00:01Z INFO  ab_av1::command::sample_encode] 50%, 90 fps, eta 2 seconds',
        '[2026-10-06T12:00:16Z INFO  ab_av1::command::encode] 25%, 24.5 fps, eta 2 minutes',
        '',
    ]));

    expect($updates)->toHaveCount(1)
        ->and($updates[0])
        ->seconds->toBe(30.0)
        ->duration->toBe(120.0)
        ->fps->toBe(24.5)
        ->speed->toBe(0.75)
        ->and($updates[0]->percentage())->toBe(25.0);
});

it('waits for the rest of a line split across chunks', function (): void {
    $parser = new AbAv1ProgressParser(duration: 60.0);

    expect($parser->feed('[2026-10-06T12:00:32Z INFO  ab_av1::command::encode] 5'))->toBe([])
        ->and($parser->feed("0%, 25 fps, eta 1 hour\n")[0]->percentage())->toBe(50.0);
});

it('reports no percentage without a duration', function (): void {
    $updates = new AbAv1ProgressParser()->feed("[2026-10-06T12:00:16Z INFO  ab_av1::command::encode] 25%, 24.5 fps, eta now\n");

    expect($updates[0])
        ->fps->toBe(24.5)
        ->and($updates[0]->percentage())->toBeNull();
});
