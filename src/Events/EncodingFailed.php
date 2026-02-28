<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EncodingFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $inputPath,
        public ?Throwable $exception = null,
        public float $executionTime = 0,
    ) {}
}
