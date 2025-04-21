<?php

namespace MoSaid\ModelReference\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasReference
{
    /**
     * Boot the HasReference trait for a model.
     */
    protected static function bootHasReference(): void
    {
        static::creating(function (Model $model): void {
            // Get the column name from model method, property or config
            $column = $model->getReferenceColumn();

            // Only generate reference if the column exists and is empty
            if (empty($model->{$column})) {
                $model->{$column} = static::generateReference($model);
            }
        });
    }

    /**
     * Generate a reference for this model.
     */
    public static function generateReference(Model $model): string
    {
        // Get configuration (from model methods, properties or config file)
        $prefix = $model->getReferencePrefix();
        $suffix = $model->getReferenceSuffix();
        $separator = $model->getReferenceSeparator();
        $length = $model->getReferenceLength();
        $characters = $model->getReferenceCharacters();
        $column = $model->getReferenceColumn();

        // Generate unique code
        do {
            // Generate random string
            $code = static::generateRandomString($length, $characters);

            // Combine parts
            $parts = [];
            if (! empty($prefix)) {
                $parts[] = $prefix;
            }
            $parts[] = $code;
            if (! empty($suffix)) {
                $parts[] = $suffix;
            }

            $reference = implode($separator, $parts);

            // Check uniqueness
            $exists = $model::where($column, $reference)->exists();
        } while ($exists);

        return $reference;
    }

    /**
     * Generate a random string.
     */
    protected static function generateRandomString(int $length, string $characters): string
    {
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Get the reference prefix.
     */
    public function getReferencePrefix(): string
    {
        return $this->referencePrefix ?? config('model-reference.prefix', '');
    }

    /**
     * Get the reference suffix.
     */
    public function getReferenceSuffix(): string
    {
        return $this->referenceSuffix ?? config('model-reference.suffix', '');
    }

    /**
     * Get the reference separator.
     */
    public function getReferenceSeparator(): string
    {
        return $this->referenceSeparator ?? config('model-reference.separator', '-');
    }

    /**
     * Get the reference length.
     */
    public function getReferenceLength(): int
    {
        return $this->referenceLength ?? config('model-reference.length', 6);
    }

    /**
     * Get the reference characters.
     */
    public function getReferenceCharacters(): string
    {
        return $this->referenceCharacters ?? config('model-reference.characters', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Get the reference column name.
     */
    public function getReferenceColumn(): string
    {
        return $this->referenceColumn ?? config('model-reference.column_name', 'reference');
    }
}
