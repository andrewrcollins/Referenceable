<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MoSaid\ModelReference\ModelReference;

class GenerateCommand extends Command
{
    public $signature = 'model-reference:generate
                        {model : The model class name}
                        {--column= : Specific reference column name}
                        {--batch=100 : Batch size for processing}
                        {--dry-run : Show what would be generated without saving}';

    public $description = 'Generate references for existing records without references';

    private ModelReference $modelReference;

    public function __construct(ModelReference $modelReference)
    {
        parent::__construct();
        $this->modelReference = $modelReference;
    }

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        
        if (!class_exists($modelClass)) {
            $this->error("Model class '{$modelClass}' does not exist.");
            return self::FAILURE;
        }
        
        $model = new $modelClass();
        
        if (!$model instanceof Model) {
            $this->error("'{$modelClass}' is not an Eloquent model.");
            return self::FAILURE;
        }
        
        $column = $this->option('column') ?? $model->getReferenceColumn();
        $batchSize = (int) $this->option('batch');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Processing model: {$modelClass}");
        $this->info("Reference column: {$column}");
        $this->info("Batch size: {$batchSize}");
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }
        
        $recordsToUpdate = $model::whereNull($column)
            ->orWhere($column, '')
            ->count();
        
        if ($recordsToUpdate === 0) {
            $this->info('No records need reference generation.');
            return self::SUCCESS;
        }
        
        $this->info("Found {$recordsToUpdate} records without references.");
        
        if (!$isDryRun && !$this->confirm('Continue with generation?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }
        
        $bar = $this->output->createProgressBar($recordsToUpdate);
        $bar->start();
        
        $processed = 0;
        $errors = 0;
        
        $model::whereNull($column)
            ->orWhere($column, '')
            ->chunkById($batchSize, function ($records) use (&$processed, &$errors, $column, $isDryRun, $bar) {
                foreach ($records as $record) {
                    try {
                        $reference = $this->modelReference->generate($record);
                        
                        if (!$isDryRun) {
                            $record->update([$column => $reference]);
                        }
                        
                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("\nError generating reference for ID {$record->id}: " . $e->getMessage());
                    }
                    
                    $bar->advance();
                }
            });
        
        $bar->finish();
        
        $this->newLine();
        $this->info("Processed: {$processed}");
        
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }
        
        if ($isDryRun) {
            $this->info('Dry run completed. No changes were saved.');
        } else {
            $this->info('Reference generation completed!');
        }
        
        return self::SUCCESS;
    }
}