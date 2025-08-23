<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class InstallCommand extends Command
{
    public $signature = 'model-reference:install
                        {--force : Force the installation even if table exists}';

    public $description = 'Install the model reference package';

    public function handle(): int
    {
        $this->info('Installing Model Reference package...');
        
        $this->createCounterTable();
        $this->publishConfig();
        
        $this->info('Model Reference package installed successfully!');
        
        return self::SUCCESS;
    }

    private function createCounterTable(): void
    {
        $tableName = config('model-reference.sequential.counter_table', 'model_reference_counters');
        
        if (Schema::hasTable($tableName) && !$this->option('force')) {
            $this->warn("Table '{$tableName}' already exists. Use --force to recreate.");
            return;
        }
        
        if (Schema::hasTable($tableName) && $this->option('force')) {
            Schema::dropIfExists($tableName);
            $this->warn("Dropped existing table '{$tableName}'");
        }
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->bigInteger('value')->default(1);
            $table->timestamps();
            
            $table->index('key');
        });
        
        $this->info("Created table '{$tableName}'");
    }

    private function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'model-reference-config',
            '--force' => $this->option('force'),
        ]);
    }
}