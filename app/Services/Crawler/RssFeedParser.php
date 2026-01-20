<?php

namespace App\Services\Crawler;

use App\Models\NewsSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class RssFeedParser
{
    public function parse(NewsSource $source): Collection
    {
        if (empty($source->feed_url)) {
            return collect();
        }

        try {
            $response = Http::timeout(30)->get($source->feed_url);

            if (!$response->successful()) {
                Log::warning("Failed to fetch RSS feed", [
                    'source' => $source->name,
                    'url' => $source->feed_url,
                    'status' => $response->status(),
                ]);
                return collect();
            }

            $content = $response->body();
            return $this->parseXml($content);
        } catch (\Exception $e) {
            Log::error("RSS feed parsing error", [
                'source' => $source->name,
                'url' => $source->feed_url,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    protected function parseXml(string $content): Collection
    {
        $items = collect();

        try {
            $xml = new SimpleXMLElement($content);

            // Try RSS 2.0 format
            if (isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $items->push($this->parseRssItem($item));
                }
            }
            // Try Atom format
            elseif (isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    $items->push($this->parseAtomEntry($entry));
                }
            }
        } catch (\Exception $e) {
            Log::error("XML parsing error", ['error' => $e->getMessage()]);
        }

        return $items->filter(fn ($item) => !empty($item['title']) && !empty($item['url']));
    }

    protected function parseRssItem(SimpleXMLElement $item): array
    {
        $pubDate = null;
        if (!empty($item->pubDate)) {
            try {
                $pubDate = new \DateTime((string) $item->pubDate);
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return [
            'title' => trim((string) $item->title),
            'url' => trim((string) $item->link),
            'published_date' => $pubDate,
        ];
    }

    protected function parseAtomEntry(SimpleXMLElement $entry): array
    {
        $url = '';
        if (isset($entry->link)) {
            foreach ($entry->link as $link) {
                $attrs = $link->attributes();
                if (isset($attrs['href'])) {
                    $url = (string) $attrs['href'];
                    if (isset($attrs['rel']) && (string) $attrs['rel'] === 'alternate') {
                        break;
                    }
                }
            }
        }

        $pubDate = null;
        $dateString = !empty($entry->published) ? (string) $entry->published : ((string) $entry->updated ?? null);
        if ($dateString) {
            try {
                $pubDate = new \DateTime($dateString);
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return [
            'title' => trim((string) $entry->title),
            'url' => trim($url),
            'published_date' => $pubDate,
        ];
    }
}
