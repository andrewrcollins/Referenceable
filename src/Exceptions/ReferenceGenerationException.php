<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable\Exceptions;

use Exception;

class ReferenceGenerationException extends Exception
{
    public static function maxRetriesReached(int $maxRetries): self
    {
        return new self("Failed to generate unique reference after {$maxRetries} attempts");
    }

    public static function invalidStrategy(string $strategy): self
    {
        return new self("Invalid reference generation strategy: {$strategy}");
    }

    public static function configurationError(string $message): self
    {
        return new self("Reference configuration error: {$message}");
    }
}
