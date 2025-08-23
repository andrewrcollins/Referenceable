<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\ModelReference;

class ValidateCommand extends Command
{
    public $signature = 'model-reference:validate
                        {model : The model class name}
                        {--column= : Specific reference column name}
                        {--fix : Fix invalid references by regenerating them}
                        {--batch=100 : Batch size for processing}';

    public $description = 'Validate references for a model';

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
        $shouldFix = $this->option('fix');
        $batchSize = (int) $this->option('batch');

        $this->info("Validating references for: {$modelClass}");
        $this->info("Reference column: {$column}");

        $totalRecords = $model::whereNotNull($column)
            ->where($column, '!=', '')
            ->count();

        if ($totalRecords === 0) {
            $this->info('No references found to validate.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalRecords} references to validate.");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $valid = 0;
        $invalid = 0;
        $fixed = 0;
        $duplicates = [];
        $seen = [];
        $invalidReferences = [];

        $model::whereNotNull($column)
            ->where($column, '!=', '')
            ->chunkById($batchSize, function ($records) use (
                &$valid, &$invalid, &$fixed, &$duplicates, &$seen, &$invalidReferences,
                $column, $shouldFix, $bar, $model
            ) {
                foreach ($records as $record) {
                    $reference = $record->{$column};

                    if (isset($seen[$reference])) {
                        $duplicates[] = [
                            'reference' => $reference,
                            'ids' => array_merge($seen[$reference], [$record->id])
                        ];
                    } else {
                        $seen[$reference] = [$record->id];
                    }

                    if ($record->validateReference()) {
                        $valid++;
                    } else {
                        $invalid++;
                        $invalidReferences[] = [
                            'id' => $record->id,
                            'reference' => $reference,
                        ];

                        if ($shouldFix) {
                            try {
                                $newReference = $this->modelReference->regenerateForModel($record);
                                $this->line("\nFixed ID {$record->id}: {$reference} -> {$newReference}");
                                $fixed++;
                                $invalid--;
                            } catch (\Exception $e) {
                                $this->error("\nFailed to fix ID {$record->id}: " . $e->getMessage());
                            }
                        }
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $this->displayResults($valid, $invalid, $fixed, $duplicates, $invalidReferences, $shouldFix);

        return $invalid > 0 || !empty($duplicates) ? self::FAILURE : self::SUCCESS;
    }

    private function displayResults(int $valid, int $invalid, int $fixed, array $duplicates, array $invalidReferences, bool $shouldFix): void
    {
        $this->info("Validation Results:");
        $this->info("- Valid references: {$valid}");
        $this->info("- Invalid references: {$invalid}");

        if ($shouldFix && $fixed > 0) {
            $this->info("- Fixed references: {$fixed}");
        }

        if (!empty($duplicates)) {
            $this->warn("- Duplicate references found: " . count($duplicates));

            $this->newLine();
            $this->warn("Duplicate References:");
            foreach ($duplicates as $duplicate) {
                $this->warn("  '{$duplicate['reference']}' used by IDs: " . implode(', ', $duplicate['ids']));
            }
        }

        if (!empty($invalidReferences) && !$shouldFix) {
            $this->newLine();
            $this->error("Invalid References:");
            foreach (array_slice($invalidReferences, 0, 10) as $invalid) {
                $this->error("  ID {$invalid['id']}: '{$invalid['reference']}'");
            }

            if (count($invalidReferences) > 10) {
                $this->error("  ... and " . (count($invalidReferences) - 10) . " more");
            }

            $this->newLine();
            $this->info("Use --fix to automatically regenerate invalid references.");
        }
    }
}
