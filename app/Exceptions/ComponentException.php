<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a component fails {@see \App\Validators\FieldValidator} during
 * registration in {@see \App\ComponentRegistry::loadComponentDefinition()}.
 */
final class ComponentException extends \Exception
{
    /**
     * @param list<string> $errors Validation errors
     */
    public static function invalid(string $componentName, array $errors): self
    {
        return new self(
            "Component '{$componentName}' has invalid configuration: " . implode(', ', $errors)
        );
    }
}
