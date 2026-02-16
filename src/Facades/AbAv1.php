<?php

namespace Foxws\AbAv1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Foxws\AbAv1\AbAv1
 */
class AbAv1 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Foxws\AbAv1\AbAv1::class;
    }
}
