<?php

declare(strict_types=1);

namespace Foxws\AbAv1\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EncodingStarted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $options  The arguments passed to ab-av1.
     */
    public function __construct(
        public string $inputPath,
        public ?array $options = null,
    ) {}
}
