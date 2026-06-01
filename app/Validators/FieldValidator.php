<?php

namespace App\Validators;

use App\Exceptions\FieldException;

/**
 * Field Validator
 *
 * Validates ACF field configurations to prevent errors during registration.
 * Ensures field types are valid and configurations are properly formatted.
 *
 * @package App\Validators
 */
class FieldValidator
{
    /** @var list<string> Valid ACF field types */
    private readonly array $validTypes;

    public function __construct()
    {
        // Must stay aligned with ComponentRegistry::addField() — no types the registry cannot register.
        $this->validTypes = [
            'text', 'textarea', 'wysiwyg', 'image', 'file', 'gallery',
            'select', 'radio', 'checkbox', 'button_group', 'true_false', 'link',
            'post_object', 'taxonomy', 'email', 'url',
            'number', 'range', 'color_picker',
            'repeater', 'group', 'tab', 'message', 'accordion',
        ];
    }

    /**
     * Validate field configuration
     *
     * @param array<string, mixed> $fieldConfig Field configuration array
     * @param string $fieldName Field name/key
     * @return array<string> Array of validation error messages (empty if valid)
     * @throws FieldException If field type is invalid
     */
    public function validate(array $fieldConfig, string $fieldName): array
    {
        $errors = [];

        // Check required fields
        if (! isset($fieldConfig['type'])) {
            $errors[] = "Field '{$fieldName}' is missing 'type'";
            return $errors;
        }

        // Validate type
        if (! in_array($fieldConfig['type'], $this->validTypes)) {
            $errors[] = "Field '{$fieldName}' has invalid type '{$fieldConfig['type']}'";
        }

        // Validate options if present
        if (isset($fieldConfig['options']) && ! is_array($fieldConfig['options'])) {
            $errors[] = "Field '{$fieldName}' options must be an array";
        }

        // Validate nested fields for repeater/group
        if (in_array($fieldConfig['type'], ['repeater', 'group']) && isset($fieldConfig['options']['sub_fields'])) {
            foreach ($fieldConfig['options']['sub_fields'] as $subFieldName => $subFieldConfig) {
                $subErrors = $this->validate($subFieldConfig, "{$fieldName}.{$subFieldName}");
                $errors = array_merge($errors, $subErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate all fields in a component configuration.
     *
     * @param array<string, array<string, mixed>> $fields Array of field configurations
     * @return list<string> Array of validation error messages (empty if all valid)
     */
    public function validateComponent(array $fields): array
    {
        $allErrors = [];

        foreach ($fields as $fieldName => $fieldConfig) {
            $errors = $this->validate($fieldConfig, $fieldName);
            $allErrors = array_merge($allErrors, $errors);
        }

        return $allErrors;
    }
}
