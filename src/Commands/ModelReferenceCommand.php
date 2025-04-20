<?php

namespace MoSaid\ModelReference\Commands;

use Illuminate\Console\Command;

class ModelReferenceCommand extends Command
{
    public $signature = 'model-reference';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
