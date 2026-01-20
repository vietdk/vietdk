<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CrawledMetadata extends Model
{
    use HasFactory;

    const STATUS_NEW = 'new';
    const STATUS_USED = 'used';
    const STATUS_SKIPPED = 'skipped';

    protected $table = 'crawled_metadata';

    protected $fillable = [
        'news_source_id',
        'title',
        'url',
        'published_date',
        'status',
    ];

    protected $casts = [
        'published_date' => 'datetime',
    ];

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function article(): HasOne
    {
        return $this->hasOne(Article::class, 'source_metadata_id');
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function markAsUsed(): void
    {
        $this->update(['status' => self::STATUS_USED]);
    }

    public function markAsSkipped(): void
    {
        $this->update(['status' => self::STATUS_SKIPPED]);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_USED => 'Used',
            self::STATUS_SKIPPED => 'Skipped',
        ];
    }

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeBySource($query, $sourceId)
    {
        return $query->where('news_source_id', $sourceId);
    }
}
