<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
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
        'original_title',
        'original_url',
        'body',
        'excerpt',
        'author_id',
        'category_id',
        'tone_id',
        'campaign_id',
        'source_metadata_id',
        'status',
        'published_at',
        'updated_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->excerpt) && !empty($article->body)) {
                $article->excerpt = Str::limit(strip_tags($article->body), 200);
            }
        });

        static::updating(function ($article) {
            // Track who last updated the article
            if (auth()->check() && !$article->isDirty('updated_by')) {
                $article->updated_by = auth()->id();
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

    public function tone(): BelongsTo
    {
        return $this->belongsTo(Tone::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sourceMetadata(): BelongsTo
    {
        return $this->belongsTo(CrawledMetadata::class, 'source_metadata_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
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
            $this->update([
                'status' => self::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
            ]);
        }
    }

    public function reject(): void
    {
        if ($this->canBeRejected()) {
            $this->update([
                'status' => self::STATUS_DRAFT,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ]);
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

    public function scopeDrafts(Builder $query): void
    {
        $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingReview(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        if ($user->role === User::ROLE_WRITER) {
            $query->where('author_id', $user->id);
        }
        // Editors/admins see all articles
    }
}
