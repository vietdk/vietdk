<?php

namespace App\Console\Commands;

use App\Jobs\CrawlNewsSource;
use App\Models\NewsSource;
use Illuminate\Console\Command;

class CrawlNews extends Command
{
    protected $signature = 'news:crawl
                            {--source= : Specific news source ID to crawl}
                            {--force : Force crawl even if not scheduled}';

    protected $description = 'Crawl news sources for metadata';

    public function handle(): int
    {
        $sourceId = $this->option('source');
        $force = $this->option('force');

        if ($sourceId) {
            $source = NewsSource::find($sourceId);

            if (!$source) {
                $this->error("News source with ID {$sourceId} not found.");
                return Command::FAILURE;
            }

            $this->crawlSource($source, $force);
        } else {
            $sources = NewsSource::where('is_active', true)->get();

            if ($sources->isEmpty()) {
                $this->info('No active news sources found.');
                return Command::SUCCESS;
            }

            $this->info("Found {$sources->count()} active news sources.");

            foreach ($sources as $source) {
                $this->crawlSource($source, $force);
            }
        }

        return Command::SUCCESS;
    }

    protected function crawlSource(NewsSource $source, bool $force): void
    {
        if (!$force && !$source->shouldCrawl()) {
            $this->line("Skipping {$source->name} - not scheduled yet.");
            return;
        }

        $this->info("Dispatching crawl job for: {$source->name}");
        CrawlNewsSource::dispatch($source);
    }
}
