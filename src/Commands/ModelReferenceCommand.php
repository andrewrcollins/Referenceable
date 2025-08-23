<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable\Commands;

use Illuminate\Console\Command;

class ModelReferenceCommand extends Command
{
    public $signature = 'referenceable
                        {--list : List all available commands}';

    public $description = 'Model Reference package management command';

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listCommands();
            return self::SUCCESS;
        }

        $this->info('Laravel Model Reference Package');
        $this->info('============================');
        $this->newLine();

        $this->info('Available Commands:');
        $this->info('  install     - Install package and create necessary tables');
        $this->info('  generate    - Generate references for records without them');
        $this->info('  validate    - Validate existing references');
        $this->info('  regenerate  - Regenerate existing references');
        $this->info('  stats       - Show reference statistics');
        $this->newLine();

        $this->info('Use --list to see the full command signatures.');
        $this->info('Use "php artisan help <command>" for detailed help on specific commands.');

        return self::SUCCESS;
    }

    private function listCommands(): void
    {
        $commands = [
            'referenceable:install' => 'Install the model reference package',
            'referenceable:generate' => 'Generate references for existing records without references',
            'referenceable:validate' => 'Validate references for a model',
            'referenceable:regenerate' => 'Regenerate references for existing records',
            'referenceable:stats' => 'Show reference statistics for a model',
        ];

        $this->info('Available Model Reference Commands:');
        $this->newLine();

        foreach ($commands as $command => $description) {
            $this->line("  <info>{$command}</info> - {$description}");
        }
    }
}
