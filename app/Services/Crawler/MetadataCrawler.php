<?php

namespace App\Services\Crawler;

use App\Models\CrawledMetadata;
use App\Models\NewsSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class MetadataCrawler
{
    protected RssFeedParser $rssFeedParser;
    protected ArticleDraftCreator $draftCreator;

    public function __construct(
        RssFeedParser $rssFeedParser,
        ArticleDraftCreator $draftCreator
    ) {
        $this->rssFeedParser = $rssFeedParser;
        $this->draftCreator = $draftCreator;
    }

    public function crawl(NewsSource $source, ?int $userId = null): int
    {
        $items = collect();

        // Try RSS feed first
        if (!empty($source->feed_url)) {
            $items = $this->rssFeedParser->parse($source);
        }

        // Fall back to HTML scraping if no RSS items
        if ($items->isEmpty() && !empty($source->base_url)) {
            $items = $this->scrapeHtml($source);
        }

        $savedCount = $this->saveMetadata($source, $items, $userId);

        // Update last crawled timestamp
        $source->update(['last_crawled_at' => now()]);

        return $savedCount;
    }

    protected function scrapeHtml(NewsSource $source): Collection
    {
        $items = collect();

        try {
            $response = Http::timeout(30)->get($source->base_url);

            if (!$response->successful()) {
                Log::warning("Failed to fetch HTML page", [
                    'source' => $source->name,
                    'url' => $source->base_url,
                    'status' => $response->status(),
                ]);
                return $items;
            }

            $html = $response->body();
            $items = $this->parseHtml($html, $source);
        } catch (\Exception $e) {
            Log::error("HTML scraping error", [
                'source' => $source->name,
                'url' => $source->base_url,
                'error' => $e->getMessage(),
            ]);
        }

        return $items;
    }

    protected function parseHtml(string $html, NewsSource $source): Collection
    {
        $items = collect();
        $selectors = $source->selectors ?? $source->getDefaultSelectors();

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Find all article links
        $linkSelector = $selectors['link'] ?? 'a';
        $links = $this->querySelectorAll($xpath, $linkSelector);

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Make URL absolute
            $url = $this->makeAbsoluteUrl($href, $source->base_url);

            // Try to find title from link or nearby elements
            $title = trim($link->textContent);
            if (empty($title) || strlen($title) < 10) {
                continue;
            }

            $items->push([
                'title' => $title,
                'url' => $url,
                'published_date' => now(),
            ]);
        }

        return $items->unique('url')->take(50);
    }

    protected function querySelectorAll(DOMXPath $xpath, string $cssSelector): array
    {
        // Convert CSS selector to XPath (simplified)
        $xpathQuery = $this->cssToXpath($cssSelector);

        try {
            $nodes = $xpath->query($xpathQuery);
            return $nodes ? iterator_to_array($nodes) : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function cssToXpath(string $css): string
    {
        // Handle multiple selectors
        $selectors = explode(',', $css);
        $xpaths = [];

        foreach ($selectors as $selector) {
            $selector = trim($selector);

            // Class selector
            if (str_starts_with($selector, '.')) {
                $class = substr($selector, 1);
                $xpaths[] = "//*[contains(@class, '{$class}')]";
            }
            // ID selector
            elseif (str_starts_with($selector, '#')) {
                $id = substr($selector, 1);
                $xpaths[] = "//*[@id='{$id}']";
            }
            // Tag with class
            elseif (preg_match('/^(\w+)\.(.+)$/', $selector, $matches)) {
                $tag = $matches[1];
                $class = $matches[2];
                $xpaths[] = "//{$tag}[contains(@class, '{$class}')]";
            }
            // Simple tag
            else {
                $xpaths[] = "//{$selector}";
            }
        }

        return implode(' | ', $xpaths);
    }

    protected function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        $basePath = $baseParts['path'] ?? '/';
        $basePath = dirname($basePath);

        return $scheme . '://' . $host . $basePath . '/' . $url;
    }

    protected function saveMetadata(NewsSource $source, Collection $items, ?int $userId = null): int
    {
        $savedCount = 0;

        foreach ($items as $item) {
            // Check for duplicate URL
            $exists = CrawledMetadata::where('url', $item['url'])->exists();

            if (!$exists) {
                $metadata = CrawledMetadata::create([
                    'news_source_id' => $source->id,
                    'title' => substr($item['title'], 0, 255),
                    'url' => $item['url'],
                    'published_date' => $item['published_date'],
                    'status' => CrawledMetadata::STATUS_NEW,
                ]);

                // Auto-create draft article
                try {
                    $this->draftCreator->createDraftFromMetadata($metadata, $userId);
                } catch (\Exception $e) {
                    Log::error('Failed to create draft article', [
                        'metadata_id' => $metadata->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $savedCount++;
            }
        }

        return $savedCount;
    }
}
