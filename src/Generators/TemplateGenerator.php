<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable\Generators;

use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Contracts\ReferenceGeneratorInterface;
use MohamedSaid\Referenceable\Exceptions\ReferenceGenerationException;

class TemplateGenerator implements ReferenceGeneratorInterface
{
    private RandomGenerator $randomGenerator;
    private SequentialGenerator $sequentialGenerator;

    public function __construct()
    {
        $this->randomGenerator = new RandomGenerator();
        $this->sequentialGenerator = new SequentialGenerator();
    }

    public function generate(Model $model, array $config = []): string
    {
        $format = $config['template']['format'] ?? '{PREFIX}{YEAR}{MONTH}{SEQ}';
        $randomLength = $config['template']['random_length'] ?? 4;
        $sequenceLength = $config['template']['sequence_length'] ?? 4;

        $placeholders = $this->buildPlaceholders($model, $config, $randomLength, $sequenceLength);

        $reference = $format;
        foreach ($placeholders as $placeholder => $value) {
            $reference = str_replace($placeholder, $value, $reference);
        }

        if (strpos($reference, '{') !== false) {
            preg_match_all('/\{([^}]+)\}/', $reference, $matches);
            if (!empty($matches[1])) {
                throw ReferenceGenerationException::configurationError(
                    'Unknown template placeholders: ' . implode(', ', $matches[1])
                );
            }
        }

        return $reference;
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
        return $strategy === 'template';
    }

    private function buildPlaceholders(Model $model, array $config, int $randomLength, int $sequenceLength): array
    {
        $now = now();

        return [
            '{PREFIX}' => $config['prefix'] ?? '',
            '{SUFFIX}' => $config['suffix'] ?? '',
            '{YEAR}' => $now->format('Y'),
            '{YEAR2}' => $now->format('y'),
            '{MONTH}' => $now->format('m'),
            '{DAY}' => $now->format('d'),
            '{RANDOM}' => $this->randomGenerator->generate($model, array_merge($config, [
                'length' => $randomLength,
                'prefix' => '',
                'suffix' => '',
            ])),
            '{SEQ}' => str_pad((string) $this->getSequentialNumber($model, $config), $sequenceLength, '0', STR_PAD_LEFT),
            '{MODEL}' => class_basename($model),
            '{TIMESTAMP}' => (string) $now->timestamp,
        ];
    }

    private function getSequentialNumber(Model $model, array $config): int
    {
        $sequentialConfig = array_merge($config, [
            'sequential' => array_merge($config['sequential'] ?? [], [
                'min_digits' => 1,
            ]),
            'prefix' => '',
            'suffix' => '',
        ]);

        $reference = $this->sequentialGenerator->generate($model, $sequentialConfig);

        return (int) $reference;
    }
}
