<?php

namespace App\Services;

use DOMDocument;

class TemplateValidator
{
    protected array $placeholders = [
        'title',
        'body',
        'excerpt',
        'author',
        'category',
        'tags',
        'tags_list',
        'article_index',
        'approved_at',
        'approved_date',
        'published_at',
        'source_url',
        'source',
        'tone',
        'title_uppercase',
        'body_excerpt',
        'export_date',
        'total_articles',
        'approved_from',
        'approved_to',
        'source_name',
        'category_name',
        'paragraph',
        'category_group',
        'group_name',
    ];

    protected array $blockTags = [
        'articles',
        'body_paragraphs',
        'group_by_category',
        'if',
    ];

    public function validate(string $template, bool $isHtml = false): array
    {
        $warnings = [];

        $warnings = array_merge($warnings, $this->validateBlocks($template));
        $warnings = array_merge($warnings, $this->validatePlaceholders($template));

        if ($isHtml) {
            $warnings = array_merge($warnings, $this->validateHtml($template));
        }

        return $warnings;
    }

    protected function validateBlocks(string $template): array
    {
        $warnings = [];

        foreach (['articles', 'body_paragraphs', 'group_by_category'] as $block) {
            $openCount = substr_count($template, '{{#' . $block . '}}');
            $closeCount = substr_count($template, '{{/' . $block . '}}');

            if ($openCount !== $closeCount) {
                $warnings[] = "Mismatched {{#{$block}}} and {{/{$block}}} tags.";
            }
        }

        $ifCount = preg_match_all('/{{#if\s+[^}]+}}/', $template);
        $endifCount = substr_count($template, '{{/if}}');
        if ($ifCount !== $endifCount) {
            $warnings[] = 'Mismatched {{#if}} and {{/if}} tags.';
        }

        return $warnings;
    }

    protected function validatePlaceholders(string $template): array
    {
        $warnings = [];

        if (!preg_match_all('/{{\s*([^}]+)\s*}}/', $template, $matches)) {
            return $warnings;
        }

        foreach ($matches[1] as $token) {
            $token = trim($token);

            if (str_starts_with($token, '#if ')) {
                $condition = trim(substr($token, 4));
                $condition = explode('|', $condition)[0];
                if (!in_array($condition, $this->placeholders, true)) {
                    $warnings[] = "Unknown conditional placeholder: {$condition}.";
                }
                continue;
            }

            if (str_starts_with($token, '#') || str_starts_with($token, '/')) {
                $tag = ltrim(str_replace(['#', '/'], '', $token));
                $tag = explode(' ', $tag)[0];
                if (!in_array($tag, $this->blockTags, true)) {
                    $warnings[] = "Unknown block tag: {$tag}.";
                }
                continue;
            }

            $placeholder = explode('|', $token)[0];
            if (!in_array($placeholder, $this->placeholders, true)) {
                $warnings[] = "Unknown placeholder: {$placeholder}.";
            }
        }

        return $warnings;
    }

    protected function validateHtml(string $template): array
    {
        $warnings = [];

        libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<html><body>' . $template . '</body></html>');
        $errors = libxml_get_errors();
        libxml_clear_errors();

        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_ERROR || $error->level === LIBXML_ERR_FATAL) {
                $warnings[] = 'HTML validation error: ' . trim($error->message);
            }
        }

        return $warnings;
    }
}
