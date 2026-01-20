<?php

namespace App\Jobs;

use App\Models\NewsSource;
use App\Services\Crawler\MetadataCrawler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlNewsSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public NewsSource $newsSource
    ) {}

    public function handle(MetadataCrawler $crawler): void
    {
        Log::info("Starting crawl for news source", [
            'source' => $this->newsSource->name,
            'id' => $this->newsSource->id,
        ]);

        try {
            $savedCount = $crawler->crawl($this->newsSource);

            Log::info("Crawl completed", [
                'source' => $this->newsSource->name,
                'items_saved' => $savedCount,
            ]);
        } catch (\Exception $e) {
            Log::error("Crawl failed", [
                'source' => $this->newsSource->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Crawl job failed permanently", [
            'source' => $this->newsSource->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
