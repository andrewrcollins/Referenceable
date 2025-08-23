<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use MoSaid\ModelReference\ModelReference;

class StatsCommand extends Command
{
    public $signature = 'model-reference:stats
                        {model : The model class name}
                        {--json : Output results as JSON}';

    public $description = 'Show reference statistics for a model';

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
        
        $stats = $this->modelReference->getStats($modelClass);
        
        if ($this->option('json')) {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }
        
        $this->displayStats($stats, $modelClass);
        
        return self::SUCCESS;
    }

    private function displayStats(array $stats, string $modelClass): void
    {
        $this->info("Reference Statistics for: " . class_basename($modelClass));
        $this->newLine();
        
        $this->info("Overview:");
        $this->line("  Total Records: " . number_format($stats['total_records']));
        $this->line("  With Reference: " . number_format($stats['with_reference']) . " ({$stats['coverage_percentage']}%)");
        $this->line("  Without Reference: " . number_format($stats['without_reference']));
        
        if (!empty($stats['top_prefixes'])) {
            $this->newLine();
            $this->info("Top Reference Prefixes:");
            
            foreach ($stats['top_prefixes'] as $prefix => $count) {
                $this->line("  {$prefix}: " . number_format($count));
            }
        }
        
        if ($stats['coverage_percentage'] < 100) {
            $this->newLine();
            $this->warn("Not all records have references. Run 'php artisan model-reference:generate {$modelClass}' to generate missing references.");
        }
        
        if ($stats['coverage_percentage'] == 100 && $stats['total_records'] > 0) {
            $this->newLine();
            $this->info("✅ All records have references!");
        }
    }
}