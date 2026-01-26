<?php

namespace App\Services\TemplateValidation;

class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param bool $isValid Whether the template is valid
     * @param array $errors List of error messages
     * @param array $warnings List of warning messages
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = []
    ) {}

    /**
     * Check if there are any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get all error messages as a single string.
     *
     * @return string
     */
    public function getErrorMessages(): string
    {
        return implode('; ', $this->errors);
    }

    /**
     * Get all warning messages as a single string.
     *
     * @return string
     */
    public function getWarningMessages(): string
    {
        return implode('; ', $this->warnings);
    }

    /**
     * Get all messages (errors and warnings) combined.
     *
     * @return array
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }
}
