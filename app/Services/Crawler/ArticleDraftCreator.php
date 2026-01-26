<?php

namespace App\Services\Crawler;

use App\Models\Article;
use App\Models\CrawledMetadata;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ArticleDraftCreator
{
    /**
     * Create a draft article from crawled metadata.
     *
     * @param CrawledMetadata $metadata The crawled metadata
     * @param int|null $userId The user ID to assign the article to (null for CLI crawls)
     * @return Article|null The created article or null if creation fails
     */
    public function createDraftFromMetadata(
        CrawledMetadata $metadata,
        ?int $userId = null
    ): ?Article {
        try {
            // Check for duplicates by source_metadata_id
            $existingArticle = Article::where('source_metadata_id', $metadata->id)->first();
            if ($existingArticle) {
                Log::info('Article already exists for metadata', [
                    'metadata_id' => $metadata->id,
                    'article_id' => $existingArticle->id,
                ]);
                return $existingArticle;
            }

            // Check for duplicates by original_url
            if ($metadata->url) {
                $existingArticle = Article::where('original_url', $metadata->url)->first();
                if ($existingArticle) {
                    Log::info('Article already exists with same URL', [
                        'url' => $metadata->url,
                        'article_id' => $existingArticle->id,
                    ]);
                    return $existingArticle;
                }
            }

            // Get default taxonomy IDs
            $categoryId = $this->getDefaultCategoryId();
            $toneId = $this->getDefaultToneId();
            $campaignId = $this->getDefaultCampaignId();
            $authorId = $this->getAuthorId($userId);

            // Validate that defaults exist
            if (!$categoryId || !$toneId || !$campaignId || !$authorId) {
                Log::error('Missing default values for article creation', [
                    'category_id' => $categoryId,
                    'tone_id' => $toneId,
                    'campaign_id' => $campaignId,
                    'author_id' => $authorId,
                ]);
                return null;
            }

            // Create the draft article
            $article = Article::create([
                'title' => $metadata->title,
                'original_title' => $metadata->title,
                'original_url' => $metadata->url,
                'body' => $metadata->description ?? '', // Use description or empty string
                'status' => Article::STATUS_DRAFT,
                'author_id' => $authorId,
                'assigned_to' => $userId, // NULL for CLI crawls
                'category_id' => $categoryId,
                'tone_id' => $toneId,
                'campaign_id' => $campaignId,
                'source_metadata_id' => $metadata->id,
                'published_at' => $metadata->published_date ?? now(),
            ]);

            // Mark metadata as used
            $metadata->markAsUsed();

            Log::info('Draft article created from crawled metadata', [
                'article_id' => $article->id,
                'metadata_id' => $metadata->id,
                'assigned_to' => $userId,
            ]);

            return $article;

        } catch (\Exception $e) {
            Log::error('Failed to create draft article from metadata', [
                'metadata_id' => $metadata->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get the default category ID for crawled articles.
     */
    protected function getDefaultCategoryId(): ?int
    {
        return Cache::get('crawler.defaults.category_id');
    }

    /**
     * Get the default tone ID for crawled articles.
     */
    protected function getDefaultToneId(): ?int
    {
        return Cache::get('crawler.defaults.tone_id');
    }

    /**
     * Get the default campaign ID for crawled articles.
     */
    protected function getDefaultCampaignId(): ?int
    {
        return Cache::get('crawler.defaults.campaign_id');
    }

    /**
     * Get the author ID for the article.
     * Uses provided user ID or falls back to system user for CLI crawls.
     */
    protected function getAuthorId(?int $userId): ?int
    {
        if ($userId) {
            return $userId;
        }

        // Use system user for CLI crawls
        return Cache::get('crawler.defaults.system_user_id');
    }
}
