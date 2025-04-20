<?php

namespace MoSaid\ModelReference\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasReference
{
    /**
     * Boot the HasReference trait for a model.
     */
    protected static function bootHasReference(): void
    {
        static::creating(function (Model $model) {
            // Get the column name from model property or config
            $column = $model->referenceColumn ?? config('model-reference.column_name', 'reference');

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
        // Get configuration (from model properties or config file)
        $prefix = $model->referencePrefix ?? config('model-reference.prefix', '');
        $suffix = $model->referenceSuffix ?? config('model-reference.suffix', '');
        $separator = $model->referenceSeparator ?? config('model-reference.separator', '-');
        $length = $model->referenceLength ?? config('model-reference.length', 6);
        $characters = $model->referenceCharacters ?? config('model-reference.characters', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $column = $model->referenceColumn ?? config('model-reference.column_name', 'reference');

        // Generate unique code
        do {
            // Generate random string
            $code = static::generateRandomString($length, $characters);

            // Combine parts
            $parts = [];
            if (!empty($prefix)) $parts[] = $prefix;
            $parts[] = $code;
            if (!empty($suffix)) $parts[] = $suffix;

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
}
