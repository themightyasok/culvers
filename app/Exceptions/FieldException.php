<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an ACF field type isn't recognised by
 * {@see \App\ComponentRegistry::addField()}.
 */
final class FieldException extends \Exception
{
    public static function invalidType(string $fieldName, string $fieldType): self
    {
        return new self("Field '{$fieldName}' has invalid type '{$fieldType}'.");
    }
}
