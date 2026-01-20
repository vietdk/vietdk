<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'title',
        'slug',
        'body',
        'excerpt',
        'author_id',
        'category_id',
        'source_metadata_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if (empty($article->excerpt) && !empty($article->body)) {
                $article->excerpt = Str::limit(strip_tags($article->body), 200);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && !$article->isDirty('slug')) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function tones(): BelongsToMany
    {
        return $this->belongsToMany(Tone::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class);
    }

    public function sourceMetadata(): BelongsTo
    {
        return $this->belongsTo(CrawledMetadata::class, 'source_metadata_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft() || $this->isRejected();
    }

    public function canBeApproved(): bool
    {
        return $this->isPendingReview();
    }

    public function canBeRejected(): bool
    {
        return $this->isPendingReview();
    }

    public function canBePublished(): bool
    {
        return $this->isApproved();
    }

    public function submitForReview(): void
    {
        if ($this->canBeSubmitted()) {
            $this->update(['status' => self::STATUS_PENDING_REVIEW]);
        }
    }

    public function approve(): void
    {
        if ($this->canBeApproved()) {
            $this->update(['status' => self::STATUS_APPROVED]);
        }
    }

    public function reject(): void
    {
        if ($this->canBeRejected()) {
            $this->update(['status' => self::STATUS_DRAFT]);
        }
    }

    public function publish(): void
    {
        if ($this->canBePublished()) {
            $this->update([
                'status' => self::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);
        }
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
