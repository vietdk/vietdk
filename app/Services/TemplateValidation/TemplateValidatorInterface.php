<?php

namespace App\Services\TemplateValidation;

interface TemplateValidatorInterface
{
    /**
     * Validate a template string.
     *
     * @param string $template The template content to validate
     * @param string $type The template type (html, text, shortcode)
     * @return ValidationResult The validation result
     */
    public function validate(string $template, string $type): ValidationResult;
}
