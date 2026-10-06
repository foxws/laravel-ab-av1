<?php

declare(strict_types=1);

use Foxws\AbAv1\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * The value after an option in a list of arguments.
 *
 * @param  list<string>  $arguments
 */
function optionValue(array $arguments, string $name): ?string
{
    $index = array_search($name, $arguments, true);

    return $index !== false ? ($arguments[$index + 1] ?? null) : null;
}
