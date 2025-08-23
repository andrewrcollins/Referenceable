<?php

declare(strict_types=1);

namespace MoSaid\ModelReference;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MoSaid\ModelReference\Contracts\ReferenceGeneratorInterface;
use MoSaid\ModelReference\Exceptions\ReferenceGenerationException;
use MoSaid\ModelReference\Exceptions\ReferenceValidationException;
use MoSaid\ModelReference\Generators\RandomGenerator;
use MoSaid\ModelReference\Generators\SequentialGenerator;
use MoSaid\ModelReference\Generators\TemplateGenerator;

class ModelReference
{
    private array $generators = [];
    private array $cachedConfig = [];

    public function __construct()
    {
        $this->registerDefaultGenerators();
    }

    public function generate(Model $model): string
    {
        $config = $this->getModelConfig($model);
        $generator = $this->getGenerator($config['strategy']);
        $column = $config['column_name'];
        $maxRetries = $config['max_retries'];
        
        $attempts = 0;
        
        do {
            $attempts++;
            $reference = $generator->generate($model, $config);
            
            if (!$this->referenceExists($model, $reference, $config)) {
                return $reference;
            }
            
            if ($attempts >= $maxRetries) {
                throw ReferenceGenerationException::maxRetriesReached($maxRetries);
            }
            
        } while (true);
    }

    public function validate(string $reference, array $config = []): bool
    {
        if (empty($config)) {
            $config = $this->getConfig();
        }
        
        $strategy = $config['strategy'] ?? 'random';
        $generator = $this->getGenerator($strategy);
        
        return $generator->validate($reference, $config);
    }

    public function generateBatch(string $modelClass, int $count, array $config = []): Collection
    {
        $references = collect();
        $model = new $modelClass();
        $finalConfig = array_merge($this->getModelConfig($model), $config);
        
        for ($i = 0; $i < $count; $i++) {
            $references->push($this->generate($model));
        }
        
        return $references;
    }

    public function regenerateForModel(Model $model): string
    {
        $newReference = $this->generate($model);
        $column = $model->getReferenceColumn();
        
        $model->update([$column => $newReference]);
        
        return $newReference;
    }

    public function getStats(string $modelClass): array
    {
        $model = new $modelClass();
        $column = $model->getReferenceColumn();
        $table = $model->getTable();
        
        $total = DB::table($table)->count();
        $withReference = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->count();
        $withoutReference = $total - $withReference;
        
        $prefixes = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->get()
            ->pluck($column)
            ->map(fn($ref) => explode('-', $ref)[0] ?? $ref)
            ->countBy()
            ->take(10);
        
        return [
            'total_records' => $total,
            'with_reference' => $withReference,
            'without_reference' => $withoutReference,
            'coverage_percentage' => $total > 0 ? round(($withReference / $total) * 100, 2) : 0,
            'top_prefixes' => $prefixes->toArray(),
        ];
    }

    public function registerGenerator(ReferenceGeneratorInterface $generator): void
    {
        $this->generators[] = $generator;
    }

    public function getGenerator(string $strategy): ReferenceGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($strategy)) {
                return $generator;
            }
        }
        
        throw ReferenceGenerationException::invalidStrategy($strategy);
    }

    public function validateBulk(Collection $references, array $config = []): array
    {
        $results = [];
        
        foreach ($references as $reference) {
            $results[$reference] = $this->validate($reference, $config);
        }
        
        return $results;
    }

    private function registerDefaultGenerators(): void
    {
        $this->registerGenerator(new RandomGenerator());
        $this->registerGenerator(new SequentialGenerator());
        $this->registerGenerator(new TemplateGenerator());
    }

    private function getModelConfig(Model $model): array
    {
        $modelClass = get_class($model);
        
        if (config('model-reference.performance.cache_config', true)) {
            $cacheKey = "model_reference_config_{$modelClass}";
            $ttl = config('model-reference.performance.cache_ttl', 60);
            
            return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($model) {
                return $model->getReferenceConfig();
            });
        }
        
        return $model->getReferenceConfig();
    }

    private function getConfig(): array
    {
        if (empty($this->cachedConfig)) {
            $this->cachedConfig = config('model-reference', []);
        }
        
        return $this->cachedConfig;
    }

    private function referenceExists(Model $model, string $reference, array $config): bool
    {
        $column = $config['column_name'];
        $scope = $config['uniqueness_scope'];
        $tenantColumn = $config['tenant_column'] ?? null;
        
        $query = match($scope) {
            'global' => DB::table($model->getTable()),
            'model' => $model::query(),
            'tenant' => $this->buildTenantQuery($model, $tenantColumn),
            default => $model::query(),
        };
        
        return $query->where($column, $reference)->exists();
    }

    private function buildTenantQuery(Model $model, ?string $tenantColumn)
    {
        if (!$tenantColumn || !$model->getAttribute($tenantColumn)) {
            return $model::query();
        }
        
        return $model::where($tenantColumn, $model->getAttribute($tenantColumn));
    }
}