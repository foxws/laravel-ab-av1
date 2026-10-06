<?php

declare(strict_types=1);

namespace Foxws\AbAv1;

use Foxws\Media\Executables\Binary;
use Illuminate\Support\Facades\Config;

enum AbAv1Executable: string implements Binary
{
    case AbAv1 = 'ab-av1';

    public function identifier(): string
    {
        return $this->value;
    }

    public function configuredPath(): string
    {
        $configured = Config::get('ab-av1.binary');

        return is_string($configured) && $configured !== '' ? $configured : $this->value;
    }

    public function environmentKey(): string
    {
        return 'AB_AV1_BINARY';
    }

    public function versionArguments(): array
    {
        return ['--version'];
    }
}
