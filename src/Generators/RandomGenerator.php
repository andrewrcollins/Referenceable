<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Generators;

use Illuminate\Database\Eloquent\Model;
use MoSaid\ModelReference\Contracts\ReferenceGeneratorInterface;
use MoSaid\ModelReference\Exceptions\ReferenceGenerationException;

class RandomGenerator implements ReferenceGeneratorInterface
{
    public function generate(Model $model, array $config = []): string
    {
        $prefix = $config['prefix'] ?? '';
        $suffix = $config['suffix'] ?? '';
        $separator = $config['separator'] ?? '-';
        $length = $config['length'] ?? 6;
        $characters = $config['characters'] ?? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $excludedChars = $config['excluded_characters'] ?? '';
        $case = $config['case'] ?? 'upper';
        
        if (!empty($excludedChars)) {
            $characters = str_replace(str_split($excludedChars), '', $characters);
        }

        $code = $this->generateRandomString($length, $characters);
        
        $code = match($case) {
            'lower' => strtolower($code),
            'upper' => strtoupper($code),
            default => $code,
        };

        $parts = array_filter([$prefix, $code, $suffix]);
        
        return implode($separator, $parts);
    }

    public function validate(string $reference, array $config = []): bool
    {
        if (empty($reference)) {
            return false;
        }

        $minLength = $config['min_length'] ?? 3;
        $maxLength = $config['max_length'] ?? 50;
        $pattern = $config['pattern'] ?? null;

        if (strlen($reference) < $minLength || strlen($reference) > $maxLength) {
            return false;
        }

        if ($pattern && !preg_match($pattern, $reference)) {
            return false;
        }

        return true;
    }

    public function supports(string $strategy): bool
    {
        return $strategy === 'random';
    }

    private function generateRandomString(int $length, string $characters): string
    {
        if (empty($characters)) {
            throw ReferenceGenerationException::configurationError('Characters string cannot be empty');
        }

        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}