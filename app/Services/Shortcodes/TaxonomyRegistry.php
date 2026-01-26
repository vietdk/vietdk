<?php

namespace App\Services\Shortcodes;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tone;

class TaxonomyRegistry
{
    public static function all(): array
    {
        return [
            'category' => [
                'label' => 'Category',
                'relation' => 'category',
                'model' => Category::class,
                'hierarchical' => true,
            ],
            'post_tag' => [
                'label' => 'Tags',
                'relation' => 'tags',
                'model' => Tag::class,
                'hierarchical' => false,
            ],
            'post_tone' => [
                'label' => 'Tone',
                'relation' => 'tone',
                'model' => Tone::class,
                'hierarchical' => false,
            ],
            'post_campaign' => [
                'label' => 'Campaign',
                'relation' => 'campaign',
                'model' => Campaign::class,
                'hierarchical' => false,
            ],
            'opm_news_type' => [
                'label' => 'News Type',
                'relation' => 'tags',
                'model' => Tag::class,
                'hierarchical' => false,
            ],
        ];
    }

    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['label']])
            ->all();
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
