<?php

use Foxws\AbAv1\MediaOpener;
use Foxws\AbAv1\Support\Encoder;

it('declares every public encoder method it forwards, so static analysis knows them', function () {
    $declared = (new ReflectionClass(MediaOpener::class))->getDocComment();

    $forwarded = collect((new ReflectionClass(Encoder::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method) => $method->isStatic() || $method->isConstructor())
        ->reject(fn (ReflectionMethod $method) => method_exists(MediaOpener::class, $method->getName()))
        ->map(fn (ReflectionMethod $method) => $method->getName());

    $missing = $forwarded->reject(fn (string $name) => preg_match("/@method .+ {$name}\\(/", $declared));

    expect($missing->values()->all())->toBe([]);
});
