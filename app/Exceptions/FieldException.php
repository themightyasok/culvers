<?php

namespace App\Exceptions;

/**
 * Field Exception
 *
 * Exception for field-related errors
 */
class FieldException extends \Exception
{
    /**
     * Create exception for invalid field type
     *
     * @param string $fieldName Field name
     * @param string $fieldType Field type
     * @return self
     */
    public static function invalidType(string $fieldName, string $fieldType): self
    {
        return new self("Field '{$fieldName}' has invalid type '{$fieldType}'.");
    }

    /**
     * Create exception for missing required field
     *
     * @param string $fieldName Field name
     * @return self
     */
    public static function missingRequired(string $fieldName): self
    {
        return new self("Required field '{$fieldName}' is missing.");
    }
}
