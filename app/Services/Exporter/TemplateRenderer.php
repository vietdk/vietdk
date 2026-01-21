<?php

namespace App\Services\Exporter;

use App\Models\Article;
use Illuminate\Support\Collection;

class TemplateRenderer
{
    public function render(string $template, Collection $articles, array $context = [], bool $plainText = false): string
    {
        $blockStart = '{{#articles}}';
        $blockEnd = '{{/articles}}';

        if (str_contains($template, $blockStart) && str_contains($template, $blockEnd)) {
            $before = strstr($template, $blockStart, true);
            $after = substr($template, strpos($template, $blockEnd) + strlen($blockEnd));
            $block = $this->extractBlock($template, $blockStart, $blockEnd);

            $renderedArticles = $articles
                ->map(fn (Article $article) => $this->replaceArticlePlaceholders($block, $article, $plainText))
                ->implode('');

            $combined = $before . $renderedArticles . $after;

            return $this->replaceGlobalPlaceholders($combined, $context);
        }

        $rendered = $articles
            ->map(fn (Article $article) => $this->replaceArticlePlaceholders($template, $article, $plainText))
            ->implode($plainText ? "\n\n" : "");

        return $this->replaceGlobalPlaceholders($rendered, $context);
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

    protected function replaceArticlePlaceholders(string $template, Article $article, bool $plainText): string
    {
        $body = $plainText ? strip_tags($article->body ?? '') : ($article->body ?? '');
        $excerpt = $plainText ? strip_tags($article->excerpt ?? '') : ($article->excerpt ?? '');

        $replacements = [
            '{{title}}' => $article->title ?? '',
            '{{body}}' => $body,
            '{{excerpt}}' => $excerpt,
            '{{author}}' => $article->author->name ?? 'Unknown',
            '{{category}}' => $article->category?->name ?? '',
            '{{tags}}' => $article->tags->pluck('name')->implode(', '),
            '{{approved_at}}' => $this->formatDate($article->approved_at),
            '{{published_at}}' => $this->formatDate($article->published_at),
            '{{source_url}}' => $article->sourceMetadata?->url ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function replaceGlobalPlaceholders(string $template, array $context): string
    {
        $replacements = [
            '{{export_date}}' => $context['export_date'] ?? now()->format('Y-m-d'),
            '{{total_articles}}' => (string) ($context['total_articles'] ?? 0),
            '{{approved_from}}' => $context['approved_from'] ?? '',
            '{{approved_to}}' => $context['approved_to'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        return $value->format('Y-m-d H:i');
    }
}
