<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletinExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'filters',
        'output_file_path',
        'articles_count',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExportTemplate::class, 'template_id');
    }

    public function getDateRange(): ?array
    {
        if (!$this->filters) {
            return null;
        }

        return [
            'from' => $this->filters['date_from'] ?? null,
            'to' => $this->filters['date_to'] ?? null,
        ];
    }

    public function getCategoryIds(): array
    {
        return $this->filters['category_ids'] ?? [];
    }

    public function getTagIds(): array
    {
        return $this->filters['tag_ids'] ?? [];
    }

    public function getArticleIds(): array
    {
        return $this->filters['article_ids'] ?? [];
    }
}
