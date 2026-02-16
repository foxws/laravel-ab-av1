<?php

namespace Foxws\AbAv1\Commands;

use Illuminate\Console\Command;

class AbAv1Command extends Command
{
    public $signature = 'laravel-ab-av1';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
