<?php

namespace App\Filament\Pages;

use App\Models\CrawledMetadata;
use App\Models\NewsSource;
use App\Services\Crawler\ArticleDraftCreator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlArticles extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Crawler';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Crawl Articles';

    protected static string $view = 'filament.pages.crawl-articles';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Article URLs')
                    ->schema([
                        Forms\Components\Textarea::make('urls')
                            ->label('Paste URLs')
                            ->rows(10)
                            ->required()
                            ->helperText('One URL per line. Direct article URLs only.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function crawl(): void
    {
        $state = $this->form->getState();
        $rawUrls = $state['urls'] ?? '';

        $lines = preg_split('/\r\n|\r|\n/', $rawUrls);
        $urls = collect($lines)
            ->map(fn ($url) => trim($url))
            ->filter()
            ->unique()
            ->values();

        if ($urls->isEmpty()) {
            Notification::make()
                ->title('Please provide at least one URL')
                ->danger()
                ->send();
            return;
        }

        $draftCreator = app(ArticleDraftCreator::class);
        $userId = auth()->id();

        $savedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($urls as $rawUrl) {
            $url = $this->normalizeUrl($rawUrl);
            if (!$url) {
                $skippedCount++;
                continue;
            }

            if (CrawledMetadata::where('url', $url)->exists()) {
                $skippedCount++;
                continue;
            }

            $title = $this->fetchTitle($url) ?? $url;
            $baseUrl = $this->getBaseUrl($url);

            if (!$baseUrl) {
                $failedCount++;
                continue;
            }

            $host = parse_url($baseUrl, PHP_URL_HOST) ?? $baseUrl;

            $source = NewsSource::firstOrCreate(
                ['base_url' => $baseUrl],
                [
                    'name' => $host,
                    'schedule' => NewsSource::SCHEDULE_DAILY,
                    'is_active' => true,
                ]
            );

            $metadata = CrawledMetadata::create([
                'news_source_id' => $source->id,
                'title' => mb_substr($title, 0, 255),
                'url' => $url,
                'published_date' => null,
                'status' => CrawledMetadata::STATUS_NEW,
            ]);

            // Auto-create draft article
            try {
                $draftCreator->createDraftFromMetadata($metadata, $userId);
            } catch (\Exception $e) {
                Log::error('Failed to create draft article from manual URL submission', [
                    'metadata_id' => $metadata->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $savedCount++;
        }

        Notification::make()
            ->title('Crawl complete')
            ->body("Submitted: {$urls->count()}, saved: {$savedCount}, skipped: {$skippedCount}, failed: {$failedCount}")
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    protected function getBaseUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';

        return $scheme . '://' . $parts['host'];
    }

    protected function fetchTitle(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML($html);
            libxml_clear_errors();

            $nodes = $doc->getElementsByTagName('title');

            if ($nodes->length === 0) {
                return null;
            }

            return trim($nodes->item(0)->textContent);
        } catch (\Exception $e) {
            return null;
        }
    }
}
