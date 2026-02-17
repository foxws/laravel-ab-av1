<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Events;

use Foxws\AbAv1\Support\EncodingResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EncodingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EncodingResult $result,
        public ?float $executionTime = null,
    ) {}
}
