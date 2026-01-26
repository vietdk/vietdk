<?php

namespace App\Services\TemplateValidation;

class ShortcodeTemplateValidator implements TemplateValidatorInterface
{
    /**
     * Validate a shortcode template.
     *
     * @param string $template The template content to validate
     * @param string $type The template type (shortcode)
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

        // 1. Check for common shortcode errors (ensure malformed tags are reported first)
        $syntaxErrors = $this->checkCommonShortcodeErrors($template);
        $errors = array_merge($errors, $syntaxErrors);

        // 2. Check for balanced shortcode tags
        $tagErrors = $this->validateShortcodeTags($template);
        $errors = array_merge($errors, $tagErrors);

        // 3. Validate Base64 args in list_posts
        $argsErrors = $this->validateListPostsArgs($template);
        $errors = array_merge($errors, $argsErrors);

        // 4. Warn on unknown shortcodes
        $unknownWarnings = $this->validateUnknownShortcodes($template);
        $warnings = array_merge($warnings, $unknownWarnings);

        // 5. Check token syntax %%prefix.field%%
        $tokenWarnings = $this->validateTokenSyntax($template);
        $warnings = array_merge($warnings, $tokenWarnings);

        $isValid = empty($errors);

        return new ValidationResult($isValid, $errors, $warnings);
    }

    /**
     * Validate that shortcode tags are properly balanced.
     *
     * @param string $template
     * @return array List of error messages
     */
    protected function validateShortcodeTags(string $template): array
    {
        $errors = [];

        // Find all opening [tag] and [tag attr="value"]
        preg_match_all('/\[(\w+)(?:\s+[^\]]+)?\]/', $template, $openMatches, PREG_OFFSET_CAPTURE);
        $openTags = $openMatches[1] ?? [];

        // Find all closing [/tag]
        preg_match_all('/\[\/(\w+)\]/', $template, $closeMatches, PREG_OFFSET_CAPTURE);
        $closeTags = $closeMatches[1] ?? [];

        // Tags that should be self-closing or don't require closing
        $selfClosing = ['time', 'bookmark', 'html'];

        // Tags that MUST have closing tags
        $requireClosing = ['list_posts', 'loop', 'section'];

        // Count occurrences for required tags
        $openCounts = [];
        foreach ($openTags as $tag) {
            $tagName = $tag[0];
            if (!isset($openCounts[$tagName])) {
                $openCounts[$tagName] = 0;
            }
            $openCounts[$tagName]++;
        }

        $closeCounts = [];
        foreach ($closeTags as $tag) {
            $tagName = $tag[0];
            if (!isset($closeCounts[$tagName])) {
                $closeCounts[$tagName] = 0;
            }
            $closeCounts[$tagName]++;
        }

        // Check required closing tags
        foreach ($requireClosing as $tag) {
            $opens = $openCounts[$tag] ?? 0;
            $closes = $closeCounts[$tag] ?? 0;

            if ($opens !== $closes) {
                if ($closes === 0 && $opens > 0) {
                    $errors[] = "Unclosed shortcode [$tag]";
                } elseif ($opens > $closes) {
                    $errors[] = "Tag [$tag] has $opens opening tag(s) but only $closes closing tag(s)";
                } elseif ($closes > $opens && $opens > 0) {
                    $errors[] = "Tag [$tag] has $closes closing tag(s) but only $opens opening tag(s)";
                }
            }
        }

        // Check for closing tags without opening tags
        foreach ($closeCounts as $tag => $count) {
            if (!isset($openCounts[$tag])) {
                $errors[] = "Closing tag [/$tag] has no matching opening tag [$tag]";
            }
        }

        return $errors;
    }

    /**
     * Validate Base64 args in list_posts shortcodes.
     *
     * @param string $template
     * @return array List of error messages
     */
    protected function validateListPostsArgs(string $template): array
    {
        $errors = [];

        // Find all [list_posts args="..."] instances
        preg_match_all('/\[list_posts\s+args="([^"]*)"/', $template, $matches);
        $argsStrings = $matches[1] ?? [];

        foreach ($argsStrings as $index => $argsBase64) {
            if ($argsBase64 === '') {
                $errors[] = 'list_posts has empty args attribute';
                continue;
            }
            // Validate Base64 encoding
            $decoded = base64_decode($argsBase64, true);

            if ($decoded === false) {
                $errors[] = "invalid Base64 encoding in list_posts args (instance " . ($index + 1) . ")";
                continue;
            }

            // Validate JSON structure
            $json = json_decode($decoded, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "invalid JSON in list_posts args (instance " . ($index + 1) . "): " . json_last_error_msg();
                continue;
            }

            // Validate JSON structure has expected fields
            if (!is_array($json)) {
                $errors[] = "list_posts args must decode to an array (instance " . ($index + 1) . ")";
            }
        }

        return $errors;
    }

    /**
     * Validate token syntax %%prefix.field%%.
     *
     * @param string $template
     * @return array List of warning messages
     */
    protected function validateTokenSyntax(string $template): array
    {
        $warnings = [];

        // Find all %%token%% patterns
        preg_match_all('/%%([^%]+)%%/', $template, $matches);
        $tokens = array_unique($matches[1] ?? []);

        $validPrefixes = ['post_data', 'taxonomy', 'post_meta', 'url', 'text'];

        foreach ($tokens as $token) {
            // Check if token has valid prefix
            $hasValidPrefix = false;
            foreach ($validPrefixes as $prefix) {
                if (strpos($token, $prefix . '.') === 0) {
                    $hasValidPrefix = true;
                    break;
                }
            }

            // Special tokens that don't need prefix
            $specialTokens = ['url', 'text'];
            if (in_array($token, $specialTokens)) {
                $hasValidPrefix = true;
            }

            if (!$hasValidPrefix) {
                $warnings[] = "Unknown token prefix in '%%$token%%'. Expected prefixes: " . implode(', ', $validPrefixes);
            }

            // Validate taxonomy token structure: taxonomy.name.index.field
            if (strpos($token, 'taxonomy.') === 0) {
                $parts = explode('.', $token);
                if (count($parts) < 4) {
                    $warnings[] = "Taxonomy token '%%$token%%' should have format: %%taxonomy.{name}.{index}.{field}%%";
                }
            }

            // Validate post_meta token structure
            if (strpos($token, 'post_meta.') === 0) {
                $parts = explode('.', $token);
                if (count($parts) < 2) {
                    $warnings[] = "Post meta token '%%$token%%' should follow format: %%post_meta.{field_name}%%";
                }
            }
        }

        return $warnings;
    }

    /**
     * Check for common shortcode syntax errors.
     *
     * @param string $template
     * @return array List of error messages
     */
    protected function checkCommonShortcodeErrors(string $template): array
    {
        $errors = [];

        // Check for malformed shortcode tags (e.g., [ list_posts] with space after bracket)
        if (preg_match('/\[\s+\w+/', $template)) {
            $errors[] = 'Malformed shortcode tag detected: space after opening bracket (should be [tag])';
        }

        if (preg_match('/\w+\s+\]/', $template)) {
            $errors[] = 'Malformed shortcode tag detected: space before closing bracket (should be [tag])';
        }

        // Check for missing quotes in args
        if (preg_match('/\[list_posts\s+args=[^"]/', $template)) {
            $errors[] = 'list_posts args must be quoted: args="..."';
        }

        if (substr_count($template, '%%') % 2 !== 0) {
            $errors[] = 'Unmatched %% token delimiter detected';
        }

        // Check for nested [loop] tags (not supported)
        if (preg_match('/\[loop[^\]]*\](?:(?!\[\/loop\]).)*\[loop[^\]]*\]/s', $template)) {
            $errors[] = 'Nested [loop] tags are not supported';
        }

        return $errors;
    }

    protected function validateUnknownShortcodes(string $template): array
    {
        $warnings = [];

        preg_match_all('/\[(\w+)(?:\s+[^\]]+)?\]/', $template, $openMatches);
        $openTags = array_unique($openMatches[1] ?? []);

        $knownTags = ['list_posts', 'loop', 'html', 'time', 'section', 'bookmark'];

        foreach ($openTags as $tag) {
            if (!in_array($tag, $knownTags, true)) {
                $warnings[] = "Unknown shortcode [$tag] detected";
            }
        }

        return $warnings;
    }
}
