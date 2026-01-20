<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    use HasFactory;

    const SCHEDULE_HOURLY = 'hourly';
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';

    protected $fillable = [
        'name',
        'base_url',
        'feed_url',
        'selectors',
        'schedule',
        'is_active',
        'last_crawled_at',
    ];

    protected $casts = [
        'selectors' => 'array',
        'is_active' => 'boolean',
        'last_crawled_at' => 'datetime',
    ];

    public function crawledMetadata(): HasMany
    {
        return $this->hasMany(CrawledMetadata::class);
    }

    public function getDefaultSelectors(): array
    {
        return [
            'title' => 'h1, .article-title, .post-title',
            'date' => 'time, .date, .published-date',
            'link' => 'a.article-link, .post-link a',
        ];
    }

    public static function getSchedules(): array
    {
        return [
            self::SCHEDULE_HOURLY => 'Hourly',
            self::SCHEDULE_DAILY => 'Daily',
            self::SCHEDULE_WEEKLY => 'Weekly',
        ];
    }

    public function shouldCrawl(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_crawled_at) {
            return true;
        }

        return match ($this->schedule) {
            self::SCHEDULE_HOURLY => $this->last_crawled_at->diffInHours(now()) >= 1,
            self::SCHEDULE_DAILY => $this->last_crawled_at->diffInDays(now()) >= 1,
            self::SCHEDULE_WEEKLY => $this->last_crawled_at->diffInWeeks(now()) >= 1,
            default => true,
        };
    }
}
