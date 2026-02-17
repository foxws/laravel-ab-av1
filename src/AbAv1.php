<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Closure;
use Foxws\AbAv1\Support\Encoder;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * Main ab-av1 facade wrapper
 *
 * Provides convenient access to ab-av1 encoding functionality.
 */
class AbAv1
{
    use ForwardsCalls;

    protected ?string $defaultDisk = null;

    protected ?Encoder $encoder = null;

    protected ?Closure $encoderResolver = null;

    public function __construct(
        ?string $defaultDisk = null,
        ?Encoder $encoder = null,
        ?Closure $encoderResolver = null
    ) {
        $this->defaultDisk = $defaultDisk;
        $this->encoder = $encoder;
        $this->encoderResolver = $encoderResolver;
    }

    protected function encoder(): Encoder
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        $resolver = $this->encoderResolver;

        return $this->encoder = $resolver();
    }

    public function new(): MediaOpener
    {
        return new MediaOpener($this->defaultDisk, $this->encoder());
    }

    /**
     * Handle dynamic method calls into MediaOpener.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->new(), $method, $parameters);
    }
}
