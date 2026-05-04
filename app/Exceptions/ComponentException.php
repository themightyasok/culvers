<?php

namespace App\Exceptions;

/**
 * Component Exception
 *
 * Base exception for component-related errors
 */
class ComponentException extends \Exception
{
    /**
     * Create exception for missing component
     *
     * @param string $componentName Component name
     * @return self
     */
    public static function missing(string $componentName): self
    {
        return new self("Component '{$componentName}' not found or could not be loaded.");
    }

    /**
     * Create exception for invalid component configuration
     *
     * @param string $componentName Component name
     * @param array $errors Validation errors
     * @return self
     */
    public static function invalid(string $componentName, array $errors): self
    {
        $message = "Component '{$componentName}' has invalid configuration: " . implode(', ', $errors);
        return new self($message);
    }

    /**
     * Create exception for template not found
     *
     * @param string $componentName Component name
     * @return self
     */
    public static function templateNotFound(string $componentName): self
    {
        return new self("Template for component '{$componentName}' not found.");
    }
}
