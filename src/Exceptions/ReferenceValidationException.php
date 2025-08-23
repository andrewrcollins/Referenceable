<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Exceptions;

use Exception;

class ReferenceValidationException extends Exception
{
    public static function invalidFormat(string $reference, string $pattern = null): self
    {
        $message = "Invalid reference format: {$reference}";
        if ($pattern) {
            $message .= " (expected pattern: {$pattern})";
        }
        
        return new self($message);
    }

    public static function tooShort(string $reference, int $minLength): self
    {
        return new self("Reference '{$reference}' is too short. Minimum length: {$minLength}");
    }

    public static function tooLong(string $reference, int $maxLength): self
    {
        return new self("Reference '{$reference}' is too long. Maximum length: {$maxLength}");
    }

    public static function alreadyExists(string $reference): self
    {
        return new self("Reference '{$reference}' already exists");
    }
}