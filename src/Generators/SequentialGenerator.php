<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MoSaid\ModelReference\Contracts\ReferenceGeneratorInterface;
use MoSaid\ModelReference\Exceptions\ReferenceGenerationException;

class SequentialGenerator implements ReferenceGeneratorInterface
{
    public function generate(Model $model, array $config = []): string
    {
        $prefix = $config['prefix'] ?? '';
        $suffix = $config['suffix'] ?? '';
        $separator = $config['separator'] ?? '-';
        $start = $config['sequential']['start'] ?? 1;
        $minDigits = $config['sequential']['min_digits'] ?? 6;
        $resetFreq = $config['sequential']['reset_frequency'] ?? 'never';
        $counterTable = $config['sequential']['counter_table'] ?? 'model_reference_counters';

        $modelClass = get_class($model);
        $resetKey = $this->getResetKey($resetFreq);
        
        $counterKey = $modelClass . ($resetKey ? ":{$resetKey}" : '');

        $nextNumber = $this->getNextSequentialNumber(
            $counterTable, 
            $counterKey, 
            $start
        );

        $formattedNumber = str_pad((string) $nextNumber, $minDigits, '0', STR_PAD_LEFT);
        
        $parts = array_filter([$prefix, $formattedNumber, $suffix]);
        
        return implode($separator, $parts);
    }

    public function validate(string $reference, array $config = []): bool
    {
        if (empty($reference)) {
            return false;
        }

        $minLength = $config['min_length'] ?? 3;
        $maxLength = $config['max_length'] ?? 50;

        return strlen($reference) >= $minLength && strlen($reference) <= $maxLength;
    }

    public function supports(string $strategy): bool
    {
        return $strategy === 'sequential';
    }

    private function getNextSequentialNumber(string $table, string $key, int $start): int
    {
        return DB::transaction(function () use ($table, $key, $start) {
            $this->ensureCounterTableExists($table);
            
            $counter = DB::table($table)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                DB::table($table)->insert([
                    'key' => $key,
                    'value' => $start,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                return $start;
            }

            $nextValue = $counter->value + 1;
            
            DB::table($table)
                ->where('key', $key)
                ->update([
                    'value' => $nextValue,
                    'updated_at' => now(),
                ]);

            return $nextValue;
        });
    }

    private function getResetKey(string $resetFreq): ?string
    {
        return match($resetFreq) {
            'daily' => now()->format('Y-m-d'),
            'monthly' => now()->format('Y-m'),
            'yearly' => now()->format('Y'),
            default => null,
        };
    }

    private function ensureCounterTableExists(string $table): void
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            throw ReferenceGenerationException::configurationError(
                "Counter table '{$table}' does not exist. Run: php artisan model-reference:install"
            );
        }
    }
}