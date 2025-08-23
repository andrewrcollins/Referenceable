<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use MoSaid\ModelReference\Contracts\ReferenceGeneratorInterface;
use MoSaid\ModelReference\Exceptions\ReferenceGenerationException;
use MoSaid\ModelReference\Exceptions\ReferenceValidationException;
use MoSaid\ModelReference\ModelReference;

trait HasReference
{
    protected static function bootHasReference(): void
    {
        static::creating(function (Model $model): void {
            $column = $model->getReferenceColumn();

            if (empty($model->{$column})) {
                $model->{$column} = $model->generateReference();
            }
        });
    }

    public function generateReference(): string
    {
        return App::make(ModelReference::class)->generate($this);
    }

    public function regenerateReference(bool $save = false): string
    {
        $column = $this->getReferenceColumn();
        $newReference = $this->generateReference();

        $this->{$column} = $newReference;

        if ($save) {
            $this->save();
        }

        return $newReference;
    }

    public function validateReference(?string $reference = null): bool
    {
        $reference = $reference ?? $this->getAttribute($this->getReferenceColumn());

        if (!$reference) {
            return false;
        }

        return App::make(ModelReference::class)->validate($reference, $this->getReferenceConfig());
    }

    public function hasReference(): bool
    {
        $column = $this->getReferenceColumn();
        return !empty($this->{$column});
    }

    public static function findByReference(string $reference): ?static
    {
        $instance = new static();
        $column = $instance->getReferenceColumn();

        return static::where($column, $reference)->first();
    }

    public function scopeWithReference(Builder $query): Builder
    {
        $column = $this->getReferenceColumn();
        return $query->whereNotNull($column)->where($column, '!=', '');
    }

    public function scopeWithoutReference(Builder $query): Builder
    {
        $column = $this->getReferenceColumn();
        return $query->where(function ($query) use ($column) {
            $query->whereNull($column)->orWhere($column, '');
        });
    }

    public function scopeReferenceStartsWith(Builder $query, string $prefix): Builder
    {
        $column = $this->getReferenceColumn();
        return $query->where($column, 'like', $prefix . '%');
    }

    public function getReferenceConfig(): array
    {
        return [
            'column_name' => $this->getReferenceColumn(),
            'strategy' => $this->getReferenceStrategy(),
            'prefix' => $this->getReferencePrefix(),
            'suffix' => $this->getReferenceSuffix(),
            'separator' => $this->getReferenceSeparator(),
            'length' => $this->getReferenceLength(),
            'characters' => $this->getReferenceCharacters(),
            'excluded_characters' => $this->getReferenceExcludedCharacters(),
            'case' => $this->getReferenceCase(),
            'sequential' => $this->getReferenceSequentialConfig(),
            'template' => $this->getReferenceTemplateConfig(),
            'validation' => $this->getReferenceValidationConfig(),
            'uniqueness_scope' => $this->getReferenceUniquenessScope(),
            'tenant_column' => $this->getReferenceTenantColumn(),
            'collision_strategy' => $this->getReferenceCollisionStrategy(),
            'max_retries' => $this->getReferenceMaxRetries(),
        ];
    }

    public function getReferenceStrategy(): string
    {
        return $this->referenceStrategy ?? config('model-reference.strategy', 'random');
    }

    public function getReferencePrefix(): string
    {
        return $this->referencePrefix ?? config('model-reference.prefix', '');
    }

    public function getReferenceSuffix(): string
    {
        return $this->referenceSuffix ?? config('model-reference.suffix', '');
    }

    public function getReferenceSeparator(): string
    {
        return $this->referenceSeparator ?? config('model-reference.separator', '-');
    }

    public function getReferenceLength(): int
    {
        return $this->referenceLength ?? config('model-reference.length', 6);
    }

    public function getReferenceCharacters(): string
    {
        return $this->referenceCharacters ?? config('model-reference.characters', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    public function getReferenceExcludedCharacters(): string
    {
        return $this->referenceExcludedCharacters ?? config('model-reference.excluded_characters', '');
    }

    public function getReferenceCase(): string
    {
        return $this->referenceCase ?? config('model-reference.case', 'upper');
    }

    public function getReferenceColumn(): string
    {
        return $this->referenceColumn ?? config('model-reference.column_name', 'reference');
    }

    public function getReferenceSequentialConfig(): array
    {
        return array_merge(
            config('model-reference.sequential', []),
            $this->referenceSequential ?? []
        );
    }

    public function getReferenceTemplateConfig(): array
    {
        return array_merge(
            config('model-reference.template', []),
            $this->referenceTemplate ?? []
        );
    }

    public function getReferenceValidationConfig(): array
    {
        return array_merge(
            config('model-reference.validation', []),
            $this->referenceValidation ?? []
        );
    }

    public function getReferenceUniquenessScope(): string
    {
        return $this->referenceUniquenessScope ?? config('model-reference.uniqueness_scope', 'model');
    }

    public function getReferenceTenantColumn(): ?string
    {
        return $this->referenceTenantColumn ?? config('model-reference.tenant_column');
    }

    public function getReferenceCollisionStrategy(): string
    {
        return $this->referenceCollisionStrategy ?? config('model-reference.collision_strategy', 'retry');
    }

    public function getReferenceMaxRetries(): int
    {
        return $this->referenceMaxRetries ?? config('model-reference.max_retries', 100);
    }


}
