<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('Foxws\\AbAv1')
    ->toUseStrictTypes();

arch('ab-av1 runs through laravel-media, not its own processes')
    ->expect('Foxws\\AbAv1')
    ->not->toUse(['Illuminate\\Support\\Facades\\Process', 'Symfony\\Component\\Process']);
