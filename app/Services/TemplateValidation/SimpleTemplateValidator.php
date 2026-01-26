<?php

namespace App\Services\TemplateValidation;

class SimpleTemplateValidator implements TemplateValidatorInterface
{
    /**
     * Validate a simple/mustache template.
     *
     * @param string $template The template content to validate
     * @param string $type The template type (html, text)
     * @return ValidationResult The validation result
     */
    public function validate(string $template, string $type): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Skip validation for empty templates
        if (empty(trim($template))) {
            $errors[] = 'Template cannot be empty';
            return new ValidationResult(false, $errors, $warnings);
        }

        // 1. Check for common syntax errors (ensure malformed tags are reported first)
        $syntaxErrors = $this->checkCommonSyntaxErrors($template);
        $errors = array_merge($errors, $syntaxErrors);

        // 2. Check for balanced block tags
        $blockErrors = $this->validateBlockTags($template);
        $errors = array_merge($errors, $blockErrors);

        // 3. Validate placeholders against allowed list
        $placeholderWarnings = $this->validatePlaceholders($template);
        $warnings = array_merge($warnings, $placeholderWarnings);

        // 4. Ensure {{#articles}} block exists (required for article templates)
        if (!$this->hasArticleBlock($template)) {
            $warnings[] = 'Template should contain {{#articles}}...{{/articles}} block for article iteration';
        }

        $isValid = empty($errors);

        return new ValidationResult($isValid, $errors, $warnings);
    }

    /**
     * Validate that all block tags are properly balanced.
     *
     * @param string $template
     * @return array List of error messages
     */
    protected function validateBlockTags(string $template): array
    {
        $errors = [];

        // Find all opening {{#block}} tags (allow optional arguments)
        preg_match_all('/\{\{#([^\s\}]+)(?:\s+[^\}]*)?\}\}/', $template, $openMatches);
        $openTags = $openMatches[1] ?? [];

        // Find all closing {{/block}} tags
        preg_match_all('/\{\{\/([^\s\}]+)\}\}/', $template, $closeMatches);
        $closeTags = $closeMatches[1] ?? [];

        // Count occurrences of each tag
        $openCounts = array_count_values($openTags);
        $closeCounts = array_count_values($closeTags);

        // Check for unmatched opening tags
        foreach ($openCounts as $tag => $count) {
            $closeCount = $closeCounts[$tag] ?? 0;
            if ($count !== $closeCount) {
                if ($closeCount === 0) {
                    $errors[] = "Unclosed block: {{#$tag}} (missing {{/$tag}})";
                } elseif ($count > $closeCount) {
                    $errors[] = "Block {{#$tag}} has $count opening tag(s) but only $closeCount closing tag(s)";
                } else {
                    $errors[] = "Block {{#$tag}} has $closeCount closing tag(s) but only $count opening tag(s)";
                }
            }
        }

        // Check for closing tags without opening tags
        foreach ($closeCounts as $tag => $count) {
            if (!isset($openCounts[$tag])) {
                $errors[] = "Closing tag {{/$tag}} has no matching opening tag {{#$tag}}";
            }
        }

        return $errors;
    }

    /**
     * Validate placeholders against the allowed list from config.
     *
     * @param string $template
     * @return array List of warning messages
     */
    protected function validatePlaceholders(string $template): array
    {
        $warnings = [];

        // Extract all {{placeholder}} tokens (excluding block tags)
        preg_match_all('/\{\{(?!#|\/|!)(\w+)(?:\|[^}]+)?\}\}/', $template, $matches);
        $placeholders = array_unique($matches[1] ?? []);

        // Get valid placeholders from config
        $validPlaceholders = $this->getValidPlaceholders();

        foreach ($placeholders as $placeholder) {
            if (!in_array($placeholder, $validPlaceholders)) {
                $warnings[] = "Unknown placeholder: {{" . $placeholder . "}}. It may not render correctly.";
            }
        }

        return $warnings;
    }

    /**
     * Check if template contains the {{#articles}} block.
     *
     * @param string $template
     * @return bool
     */
    protected function hasArticleBlock(string $template): bool
    {
        return preg_match('/\{\{#articles\}\}/', $template) === 1;
    }

    /**
     * Check for common syntax errors in templates.
     *
     * @param string $template
     * @return array List of error messages
     */
    protected function checkCommonSyntaxErrors(string $template): array
    {
        $errors = [];

        // Check for malformed tags (e.g., {{ #articles }} with spaces)
        if (preg_match('/\{\{\s+#/', $template)) {
            $errors[] = 'Malformed block tag detected: space after {{ (should be {{#tag}})';
        }

        if (preg_match('/#\s+\w+\}\}/', $template)) {
            $errors[] = 'Malformed block tag detected: space before tag name (should be {{#tag}})';
        }

        // Check for single curly braces (common mistake)
        if (preg_match('/(?<!\{)\{(?!\{)/', $template)) {
            $warnings[] = 'Single curly braces detected. Did you mean to use double curly braces {{}}?';
            // Treating this as warning since it might be intentional HTML/CSS
        }

        return $errors;
    }

    /**
     * Get list of valid placeholders from configuration.
     *
     * @return array
     */
    protected function getValidPlaceholders(): array
    {
        $configPlaceholders = config('templates.placeholders', []);

        $valid = [];

        // Collect article placeholders
        if (isset($configPlaceholders['article'])) {
            $valid = array_merge($valid, array_keys($configPlaceholders['article']));
        }

        // Collect global placeholders
        if (isset($configPlaceholders['global'])) {
            $valid = array_merge($valid, array_keys($configPlaceholders['global']));
        }

        // Add commonly used placeholders that might not be in config
        $commonPlaceholders = [
            'title', 'body', 'excerpt', 'author', 'category', 'tags',
            'approved_at', 'published_at', 'source_url', 'source', 'tone',
            'export_date', 'total_articles', 'approved_from', 'approved_to',
            'category_group', 'source_name', 'article_index', 'approved_date',
            'tags_list', 'title_uppercase', 'category_name', 'paragraph'
        ];

        return array_unique(array_merge($valid, $commonPlaceholders));
    }
}
