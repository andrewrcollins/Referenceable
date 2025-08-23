<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\ModelReference;

class RegenerateCommand extends Command
{
    public $signature = 'model-reference:regenerate
                        {model : The model class name}
                        {--id= : Specific record ID to regenerate}
                        {--all : Regenerate all references (use with caution)}
                        {--batch=100 : Batch size for processing}
                        {--dry-run : Show what would be regenerated without saving}';

    public $description = 'Regenerate references for existing records';

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

        $specificId = $this->option('id');
        $regenerateAll = $this->option('all');
        $batchSize = (int) $this->option('batch');
        $isDryRun = $this->option('dry-run');

        if (!$specificId && !$regenerateAll) {
            $this->error('You must specify either --id or --all option.');
            return self::FAILURE;
        }

        if ($specificId && $regenerateAll) {
            $this->error('Cannot use both --id and --all options together.');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }

        if ($specificId) {
            return $this->regenerateSingle($model, $specificId, $isDryRun);
        }

        return $this->regenerateAll($model, $batchSize, $isDryRun);
    }

    private function regenerateSingle(Model $model, int $id, bool $isDryRun): int
    {
        $record = $model::find($id);

        if (!$record) {
            $this->error("Record with ID {$id} not found.");
            return self::FAILURE;
        }

        $column = $record->getReferenceColumn();
        $oldReference = $record->{$column};

        try {
            $newReference = $this->modelReference->generate($record);

            $this->info("ID {$id}:");
            $this->info("  Old: " . ($oldReference ?: '(empty)'));
            $this->info("  New: {$newReference}");

            if (!$isDryRun) {
                $record->update([$column => $newReference]);
                $this->info("✅ Reference updated successfully!");
            }

        } catch (\Exception $e) {
            $this->error("Failed to regenerate reference: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function regenerateAll(Model $model, int $batchSize, bool $isDryRun): int
    {
        $column = $model->getReferenceColumn();

        $totalRecords = $model::whereNotNull($column)
            ->where($column, '!=', '')
            ->count();

        if ($totalRecords === 0) {
            $this->info('No records with references found.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  This will regenerate ALL {$totalRecords} references!");
        $this->warn('⚠️  This action cannot be undone and may break existing integrations!');

        if (!$isDryRun && !$this->confirm('Are you absolutely sure you want to continue?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $errors = 0;

        $model::whereNotNull($column)
            ->where($column, '!=', '')
            ->chunkById($batchSize, function ($records) use (&$processed, &$errors, $column, $isDryRun, $bar) {
                foreach ($records as $record) {
                    try {
                        $oldReference = $record->{$column};
                        $newReference = $this->modelReference->generate($record);

                        if (!$isDryRun) {
                            $record->update([$column => $newReference]);
                        }

                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("\nError regenerating reference for ID {$record->id}: " . $e->getMessage());
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
            $this->info('✅ Reference regeneration completed!');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
