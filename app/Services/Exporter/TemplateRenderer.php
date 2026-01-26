<?php

namespace App\Services\Exporter;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;

class TemplateRenderer
{
    public function render(string $template, Collection $articles, array $context = [], bool $plainText = false): string
    {
        $template = $this->renderGroupByCategoryBlock($template, $articles, $context, $plainText);
        $template = $this->replaceCategoryGroupPlaceholder($template, $articles, $context, $plainText);

        $blockStart = '{{#articles}}';
        $blockEnd = '{{/articles}}';

        if (str_contains($template, $blockStart) && str_contains($template, $blockEnd)) {
            $before = strstr($template, $blockStart, true);
            $after = substr($template, strpos($template, $blockEnd) + strlen($blockEnd));
            $block = $this->extractBlock($template, $blockStart, $blockEnd);

            $renderedArticles = $this->renderArticles($block, $articles, $plainText);
            $combined = $before . $renderedArticles . $after;

            return $this->replaceGlobalPlaceholders($combined, $context);
        }

        $rendered = $this->renderArticles($template, $articles, $plainText, $plainText ? "\n\n" : '');

        return $this->replaceGlobalPlaceholders($rendered, $context);
    }

    protected function renderArticles(string $template, Collection $articles, bool $plainText, string $separator = ''): string
    {
        return $articles
            ->values()
            ->map(fn (Article $article, int $index) => $this->replaceArticlePlaceholders($template, $article, $index + 1, $plainText))
            ->implode($separator);
    }

    protected function extractBlock(string $template, string $start, string $end): string
    {
        $startPos = strpos($template, $start);
        $endPos = strpos($template, $end);

        if ($startPos === false || $endPos === false || $endPos <= $startPos) {
            return '';
        }

        $blockStart = $startPos + strlen($start);

        return substr($template, $blockStart, $endPos - $blockStart);
    }

    protected function replaceArticlePlaceholders(string $template, Article $article, int $index, bool $plainText): string
    {
        $body = $plainText ? strip_tags($article->body ?? '') : ($article->body ?? '');
        $excerpt = $plainText ? strip_tags($article->excerpt ?? '') : ($article->excerpt ?? '');

        $template = $this->renderBodyParagraphs($template, $body, $plainText);

        $values = [
            'title' => $article->title ?? '',
            'body' => $body,
            'excerpt' => $excerpt,
            'author' => $article->author->name ?? 'Unknown',
            'category' => $article->category?->name ?? '',
            'tags' => $article->tags->pluck('name')->implode(', '),
            'tags_list' => $this->formatTagsList($article->tags->pluck('name')->all(), $plainText),
            'article_index' => (string) $index,
            'approved_at' => $article->approved_at,
            'approved_date' => $article->approved_at,
            'published_at' => $article->published_at,
            'source_url' => $article->sourceMetadata?->url ?? '',
            'source' => $article->sourceMetadata?->newsSource?->name ?? '',
            'tone' => $article->tone?->name ?? '',
            'title_uppercase' => Str::upper($article->title ?? ''),
            'body_excerpt' => strip_tags($article->body ?? ''),
        ];

        $template = $this->applyConditionals($template, $values);

        return $this->replaceTokens($template, $values, [
            'approved_at' => 'Y-m-d H:i',
            'published_at' => 'Y-m-d H:i',
            'approved_date' => 'Y-m-d',
        ]);
    }

    protected function replaceGlobalPlaceholders(string $template, array $context): string
    {
        $values = array_merge([
            'export_date' => $context['export_date'] ?? now(),
            'total_articles' => (string) ($context['total_articles'] ?? 0),
            'approved_from' => $context['approved_from'] ?? '',
            'approved_to' => $context['approved_to'] ?? '',
            'source_name' => $context['source_name'] ?? '',
        ], $context);

        $template = $this->applyConditionals($template, $values);

        return $this->replaceTokens($template, $values, [
            'export_date' => 'Y-m-d',
        ]);
    }

    protected function replaceTokens(string $template, array $values, array $dateDefaults = []): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)(?:\|([^}]+))?\s*}}/', function (array $matches) use ($values, $dateDefaults) {
            $key = $matches[1];
            $pipe = isset($matches[2]) ? trim($matches[2]) : null;

            if (!array_key_exists($key, $values)) {
                return '';
            }

            $value = $values[$key];
            if ($value === null || $value === '') {
                if (array_key_exists($key, $dateDefaults)) {
                    return '';
                }

                return $pipe !== null ? $pipe : '';
            }

            if ($value instanceof CarbonInterface) {
                $format = $dateDefaults[$key] ?? 'Y-m-d H:i';
                if ($pipe && $this->looksLikeDateFormat($pipe)) {
                    $format = $pipe;
                }
                $format = $this->normalizeDateFormat($format);
                return $value->format($format);
            }

            $stringValue = (string) $value;
            if ($pipe !== null && ctype_digit($pipe)) {
                return Str::limit($stringValue, (int) $pipe, '');
            }

            return $stringValue;
        }, $template);
    }

    protected function normalizeDateFormat(string $format): string
    {
        return str_replace(
            ['yyyy', 'yy', 'mm', 'dd'],
            ['Y', 'y', 'm', 'd'],
            $format
        );
    }

    protected function looksLikeDateFormat(string $format): bool
    {
        return (bool) preg_match('/[dmyHis]/i', $format);
    }

    protected function applyConditionals(string $template, array $values): string
    {
        return preg_replace_callback('/{{#if\s+([a-zA-Z0-9_]+)\s*}}(.*?){{\/if}}/s', function (array $matches) use ($values) {
            $key = $matches[1];
            $content = $matches[2] ?? '';
            $value = $values[$key] ?? '';

            if ($value instanceof CarbonInterface) {
                return $value ? $content : '';
            }

            return $value !== '' ? $content : '';
        }, $template);
    }

    protected function renderBodyParagraphs(string $template, string $body, bool $plainText): string
    {
        $blockStart = '{{#body_paragraphs}}';
        $blockEnd = '{{/body_paragraphs}}';

        if (!str_contains($template, $blockStart) || !str_contains($template, $blockEnd)) {
            return $template;
        }

        $block = $this->extractBlock($template, $blockStart, $blockEnd);
        $paragraphs = $this->splitParagraphs($body);

        $rendered = collect($paragraphs)
            ->map(function (string $paragraph) use ($block) {
                return str_replace('{{paragraph}}', $paragraph, $block);
            })
            ->implode($plainText ? "\n" : '');

        return str_replace($blockStart . $block . $blockEnd, $rendered, $template);
    }

    protected function splitParagraphs(string $body): array
    {
        $normalized = preg_replace('/\r\n?/', "\n", $body);
        $paragraphs = preg_split('/\n{2,}/', trim($normalized));

        return array_values(array_filter(array_map('trim', $paragraphs), fn ($value) => $value !== ''));
    }

    protected function formatTagsList(array $tags, bool $plainText): string
    {
        if (empty($tags)) {
            return '';
        }

        if ($plainText) {
            return collect($tags)->map(fn ($tag) => '- ' . $tag)->implode("\n");
        }

        $items = collect($tags)
            ->map(fn ($tag) => '<li>' . e($tag) . '</li>')
            ->implode('');

        return '<ul>' . $items . '</ul>';
    }

    protected function replaceCategoryGroupPlaceholder(string $template, Collection $articles, array $context, bool $plainText): string
    {
        if (!str_contains($template, '{{category_group}}')) {
            return $template;
        }

        return str_replace(
            '{{category_group}}',
            $this->buildCategoryGroup($articles, $context, $plainText),
            $template
        );
    }

    protected function buildCategoryGroup(Collection $articles, array $context, bool $plainText): string
    {
        $groups = $articles->groupBy(fn (Article $article) => $article->category?->name ?? 'Uncategorized');
        $separator = $plainText ? "\n\n" : '<br><br>';
        $showHeaders = $context['show_group_headers'] ?? true;
        $headerFormat = $context['group_header_format'] ?? '=== {{group_name}} ===';

        return $groups->map(function (Collection $group, string $categoryName) use ($plainText, $showHeaders, $headerFormat) {
            $title = '';
            if ($showHeaders) {
                if ($plainText) {
                    $title = str_replace('{{group_name}}', $categoryName, $headerFormat);
                } else {
                    $title = '<h3>' . e(str_replace('{{group_name}}', $categoryName, $headerFormat)) . '</h3>';
                }
            }
            $items = $group
                ->values()
                ->map(fn (Article $article, int $index) => ($plainText ? '- ' : '<div>') . ($index + 1) . '. ' . e($article->title ?? '') . ($plainText ? '' : '</div>'))
                ->implode($plainText ? "\n" : '');

            if ($title !== '') {
                return $title . ($plainText ? "\n" : '') . $items;
            }

            return $items;
        })->implode($separator);
    }

    protected function renderGroupByCategoryBlock(string $template, Collection $articles, array $context, bool $plainText): string
    {
        $pattern = '/{{#group_by_category}}(.*?){{\/group_by_category}}/s';

        if (!preg_match($pattern, $template)) {
            return $template;
        }

        $groups = $articles->groupBy(fn (Article $article) => $article->category?->name ?? 'Uncategorized');

        return preg_replace_callback($pattern, function (array $matches) use ($groups, $context, $plainText) {
            $block = $matches[1] ?? '';

            $renderedGroups = $groups->map(function (Collection $group, string $categoryName) use ($block, $context, $plainText) {
                $groupBlock = str_replace('{{category_name}}', $categoryName, $block);

                if (str_contains($groupBlock, '{{#articles}}') && str_contains($groupBlock, '{{/articles}}')) {
                    $before = strstr($groupBlock, '{{#articles}}', true);
                    $after = substr($groupBlock, strpos($groupBlock, '{{/articles}}') + strlen('{{/articles}}'));
                    $articleBlock = $this->extractBlock($groupBlock, '{{#articles}}', '{{/articles}}');
                    $renderedArticles = $this->renderArticles($articleBlock, $group, $plainText);
                    $groupBlock = $before . $renderedArticles . $after;
                } else {
                    $groupBlock = $this->renderArticles($groupBlock, $group, $plainText);
                }

                return $this->replaceGlobalPlaceholders($groupBlock, $context);
            })->implode($plainText ? "\n\n" : '');

            return $renderedGroups;
        }, $template);
    }
}
