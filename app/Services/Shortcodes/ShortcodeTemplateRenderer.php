<?php

namespace App\Services\Shortcodes;

use App\Models\Article;
use App\Models\CrawledMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShortcodeTemplateRenderer
{
    public function render(string $template, Builder $baseQuery, array $context = []): string
    {
        if (config('templates.rendering.enable_logging', false)) {
            Log::info('Shortcode template rendering started', [
                'template_length' => strlen($template),
                'context_keys' => array_keys($context),
            ]);
        }

        try {
            $content = str_replace(['[section]', '[/section]'], '', $template);

            $content = $this->renderListPostsBlocks($content, $baseQuery, $context);
            $content = $this->renderHtmlBlocks($content);
            $content = $this->renderBookmarks($content, $context);
            $content = $this->renderTimeBlocks($content, $context);
            $content = $this->stripRemainingShortcodes($content);

            if (config('templates.rendering.enable_logging', false)) {
                Log::info('Shortcode template rendering completed', [
                    'output_length' => strlen($content),
                ]);
            }

            return $content;
        } catch (\Exception $e) {
            Log::error('Shortcode template rendering failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function renderListPostsBlocks(string $content, Builder $baseQuery, array $context): string
    {
        $pattern = '/\[list_posts\s+args="([^"]+)"\](.*?)\[\/list_posts\]/s';
        $codec = new ShortcodeArgsCodec();

        return preg_replace_callback($pattern, function (array $matches) use ($baseQuery, $context, $codec) {
            $args = $codec->decode($matches[1] ?? '');
            $block = $matches[2] ?? '';
            $query = $this->applyTaxQuery(clone $baseQuery, $args);
            $articles = $query->get();

            $loopPattern = '/\[loop(?:\s+[^\]]+)?\](.*?)\[\/loop\]/s';
            $loopHtml = '';

            if (preg_match($loopPattern, $block, $loopMatches)) {
                $loopTemplate = $loopMatches[1] ?? '';
                foreach ($articles as $article) {
                    $loopHtml .= $this->renderLoopContent($loopTemplate, $article, $context);
                }
                $block = preg_replace($loopPattern, $loopHtml, $block, 1);
            }

            $block = $this->renderHtmlBlocks($block);
            $block = $this->renderBookmarks($block, $context);
            $block = $this->renderTimeBlocks($block, $context);
            $block = $this->stripRemainingShortcodes($block);

            return $block;
        }, $content) ?? $content;
    }

    protected function renderLoopContent(string $content, Article $article, array $context): string
    {
        $rendered = $this->renderHtmlBlocks($content);
        $rendered = $this->replaceTokens($rendered, $article, $context);
        $rendered = $this->renderBookmarks($rendered, $context, $article);
        $rendered = $this->renderTimeBlocks($rendered, $context, $article);
        $rendered = $this->stripRemainingShortcodes($rendered);

        return $rendered;
    }

    protected function renderHtmlBlocks(string $content): string
    {
        $content = preg_replace('/\[html[^\]]*\]/', '', $content) ?? $content;
        $content = str_replace('[/html]', '', $content);

        return $content;
    }

    protected function renderBookmarks(string $content, array $context, ?Article $article = null): string
    {
        return preg_replace_callback('/\[bookmark\s+name="([^"]+)"\]/', function (array $matches) use ($article, $context) {
            $name = $matches[1] ?? '';
            if ($article) {
                $name = $this->replaceTokens($name, $article, $context);
            }

            return '<a id="' . e($name) . '"></a>';
        }, $content) ?? $content;
    }

    protected function renderTimeBlocks(string $content, array $context, ?Article $article = null): string
    {
        $pattern = '/\[time([^\]]*)\](.*?)\[\/time\]/s';

        return preg_replace_callback($pattern, function (array $matches) use ($article, $context) {
            $attributes = $this->parseAttributes($matches[1] ?? '');
            $inner = trim($matches[2] ?? '');

            if ($article) {
                $inner = $this->replaceTokens($inner, $article, $context);
            }

            try {
                $date = $inner !== '' ? Carbon::parse($inner) : now();
            } catch (\Throwable $exception) {
                $date = now();
            }

            $increase = $attributes['increase'] ?? null;
            if ($increase) {
                try {
                    $date = $date->copy()->modify($increase);
                } catch (\Throwable $exception) {
                    $date = $date->copy();
                }
            }

            $format = $attributes['format'] ?? 'Y-m-d';

            return $date->format($format);
        }, $content) ?? $content;
    }

    protected function parseAttributes(string $input): array
    {
        $attributes = [];
        preg_match_all('/(\w+)="([^"]*)"/', $input, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }

    protected function replaceTokens(string $content, Article $article, array $context): string
    {
        $content = preg_replace_callback('/%%post_data\.([\w_]+)%%/', function (array $matches) use ($article) {
            $key = $matches[1] ?? '';

            try {
                $value = match ($key) {
                    'ID' => (string) $article->id,
                    'post_date' => $this->getArticleDate($article)->toDateString(),
                    default => (string) data_get($article, $key, ''),
                };

                // Apply HTML escaping unless it's a safe HTML field
                return $this->escapeValue($value, $key);
            } catch (\Exception $e) {
                Log::warning('Token replacement failed', [
                    'token' => $matches[0],
                    'article_id' => $article->id,
                    'error' => $e->getMessage(),
                ]);
                return '[ERROR: ' . $matches[0] . ']';
            }
        }, $content) ?? $content;

        $content = preg_replace_callback('/%%taxonomy\.([\w_]+)\.(\d+)\.([\w_]+)%%/', function (array $matches) use ($article) {
            $taxonomyKey = $matches[1] ?? '';
            $index = (int) ($matches[2] ?? 0);
            $field = $matches[3] ?? 'name';

            return $this->getTaxonomyValue($article, $taxonomyKey, $index, $field);
        }, $content) ?? $content;

        $content = preg_replace_callback('/%%post_meta\.([\w_\.]+)%%/', function (array $matches) use ($article) {
            return $this->getMetaValue($article, $matches[1] ?? '');
        }, $content) ?? $content;

        $content = str_replace(['%%url%%', '%%text%%'], '', $content);
        $content = $this->replaceDomainShortcode($content);
        $content = $this->stripWrapperShortcodes($content, 'group_posts');
        $content = $this->stripWrapperShortcodes($content, 'related_links');

        return $content;
    }

    protected function getArticleDate(Article $article): Carbon
    {
        return $article->published_at
            ?? $article->approved_at
            ?? $article->created_at
            ?? now();
    }

    protected function getTaxonomyValue(Article $article, string $taxonomyKey, int $index, string $field): string
    {
        $taxonomy = TaxonomyRegistry::get($taxonomyKey);
        if (!$taxonomy) {
            return '';
        }

        $relation = $taxonomy['relation'];
        $related = $article->{$relation} ?? null;

        $value = '';
        if ($related instanceof Collection) {
            $item = $related->values()->get($index);
            $value = $item ? (string) data_get($item, $field, '') : '';
        } else {
            $value = (string) data_get($related, $field, '');
        }

        // Escape taxonomy values for security (they're user input)
        return e($value);
    }

    protected function getMetaValue(Article $article, string $key): string
    {
        $value = match ($key) {
            'opm_title_en.0' => (string) ($article->title ?? ''),
            'opm_title_vi.0' => (string) ($article->title_vi ?? $article->original_title ?? ''),
            'opm_content_en.0' => (string) ($article->excerpt ?? Str::limit(strip_tags($article->body ?? ''), 200)),
            '_kdn_source_urls.0.0' => (string) ($article->sourceMetadata?->url ?? ''),
            'source_name.0' => $this->getSourceDomain($article->sourceMetadata),
            default => '',
        };

        // Escape meta values for security
        // Note: opm_content_en.0 already has HTML stripped, so safe to escape
        return e($value);
    }

    protected function getSourceDomain(?CrawledMetadata $metadata): string
    {
        $url = $metadata?->url ?? '';
        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if ($host === '') {
            return '';
        }

        return preg_replace('/^www\./i', '', $host) ?? $host;
    }

    /**
     * Escape value for HTML output unless it's a known HTML-safe field.
     *
     * @param string $value The value to potentially escape
     * @param string $fieldKey The field key to check against safe fields
     * @return string The escaped or unescaped value
     */
    protected function escapeValue(string $value, string $fieldKey): string
    {
        $htmlSafeFields = config('templates.rendering.html_safe_fields', [
            'post_content',
            'post_excerpt',
            'body',
            'excerpt',
        ]);

        // Don't escape if it's a known HTML content field
        if (in_array($fieldKey, $htmlSafeFields)) {
            return $value;
        }

        // Escape for security
        return e($value);
    }

    protected function replaceDomainShortcode(string $content): string
    {
        return preg_replace_callback('/\[domain_text\s+url="([^"]*)"(?:\s+force="([^"]*)")?\]/', function (array $matches) {
            $url = $matches[1] ?? '';
            $force = $matches[2] ?? '';

            if ($force !== '') {
                return $force;
            }

            $host = parse_url($url, PHP_URL_HOST) ?? '';
            return $host !== '' ? preg_replace('/^www\./i', '', $host) : '';
        }, $content) ?? $content;
    }

    protected function stripWrapperShortcodes(string $content, string $tag): string
    {
        $content = preg_replace('/\[' . preg_quote($tag, '/') . '[^\]]*\]/', '', $content) ?? $content;
        $content = preg_replace('/\[\/' . preg_quote($tag, '/') . '\]/', '', $content) ?? $content;

        return $content;
    }

    protected function stripRemainingShortcodes(string $content): string
    {
        return preg_replace('/\[[^\]]+\]/', '', $content) ?? $content;
    }

    protected function applyTaxQuery(Builder $query, array $args): Builder
    {
        $taxQuery = Arr::get($args, 'tax_query', []);
        $relation = strtoupper((string) Arr::get($taxQuery, 'relation', 'AND'));
        $filters = array_filter($taxQuery, fn ($item) => is_array($item));

        if (empty($filters)) {
            return $query;
        }

        $query->where(function (Builder $inner) use ($filters, $relation) {
            foreach ($filters as $filter) {
                $this->applyTaxFilter($inner, $filter, $relation === 'OR');
            }
        });

        return $query;
    }

    protected function applyTaxFilter(Builder $query, array $filter, bool $useOr): void
    {
        $taxonomyKey = Arr::get($filter, 'taxonomy');
        $taxonomy = TaxonomyRegistry::get((string) $taxonomyKey);
        if (!$taxonomy) {
            return;
        }

        $field = Arr::get($filter, 'field', 'id');
        $operator = strtoupper((string) Arr::get($filter, 'operator', 'IN'));
        $terms = array_values(array_filter((array) Arr::get($filter, 'terms', [])));
        $includeChildren = Arr::get($filter, 'children', 'true') === 'true';

        if (empty($terms)) {
            return;
        }

        if ($taxonomy['hierarchical'] && $includeChildren) {
            $terms = $this->expandChildTerms($taxonomy, $terms, $field);
        }

        $relation = $taxonomy['relation'];
        if ($operator === 'NOT IN') {
            $method = $useOr ? 'orWhereDoesntHave' : 'whereDoesntHave';
            $query->{$method}($relation, function (Builder $relationQuery) use ($field, $terms, $taxonomy) {
                if ($taxonomy['hierarchical'] && $field === 'id') {
                    $relationQuery->whereIn('id', $terms);
                } else {
                    $relationQuery->whereIn($field, $terms);
                }
            });
            return;
        }

        $method = $useOr ? 'orWhereHas' : 'whereHas';
        $query->{$method}($relation, function (Builder $relationQuery) use ($field, $terms, $taxonomy) {
            if ($taxonomy['hierarchical'] && $field === 'id') {
                $relationQuery->whereIn('id', $terms);
            } else {
                $relationQuery->whereIn($field, $terms);
            }
        });
    }

    protected function expandChildTerms(array $taxonomy, array $terms, string $field): array
    {
        if ($field !== 'id' || empty($taxonomy['model'])) {
            return $terms;
        }

        $model = $taxonomy['model'];
        $ids = collect($terms)->map(fn ($term) => (int) $term)->filter()->all();
        if (empty($ids)) {
            return $terms;
        }

        $children = $model::query()
            ->whereIn('parent_id', $ids)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($ids, $children)));
    }
}
