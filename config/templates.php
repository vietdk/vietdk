<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Template System Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures the export template system, including meta field
    | mappings, taxonomy definitions, and rendering options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Meta Field Mappings
    |--------------------------------------------------------------------------
    |
    | Maps WordPress/legacy meta field keys to Laravel Article model attributes.
    | Supports direct attributes, relations, fallbacks, and transformations.
    |
    | Configuration options:
    | - model: The model class (usually Article)
    | - attribute: The attribute name on the model
    | - relation: Optional relation name to access related data
    | - fallback: Optional fallback attribute if main attribute is null
    | - escape: Whether to HTML-escape the value (default: true)
    | - transform: Optional closure to transform the value
    |
    */

    'meta_mappings' => [
        'opm_title_en' => [
            'model' => \App\Models\Article::class,
            'attribute' => 'title',
            'escape' => true,
        ],
        'opm_title_vi' => [
            'model' => \App\Models\Article::class,
            'attribute' => 'title_vi',
            'fallback' => 'original_title',
            'escape' => true,
        ],
        'opm_content_en' => [
            'model' => \App\Models\Article::class,
            'attribute' => 'excerpt',
            'fallback' => 'body',
            'escape' => false,  // HTML content - controlled escaping
            'transform' => function ($value, $article) {
                if ($value) {
                    return $value;
                }
                // Fallback to truncated body if no excerpt
                $body = $article->body ?? '';
                return \Illuminate\Support\Str::limit(strip_tags($body), 200);
            },
        ],
        '_kdn_source_urls' => [
            'model' => \App\Models\Article::class,
            'relation' => 'sourceMetadata',
            'attribute' => 'url',
            'escape' => true,
        ],
        'source_name' => [
            'model' => \App\Models\Article::class,
            'relation' => 'sourceMetadata',
            'attribute' => 'url',
            'escape' => true,
            'transform' => function ($url) {
                if (!$url) {
                    return '';
                }
                $domain = parse_url($url, PHP_URL_HOST) ?? '';
                return preg_replace('/^www\./i', '', $domain) ?? $domain;
            },
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomy Registry
    |--------------------------------------------------------------------------
    |
    | Defines available taxonomies for filtering and grouping articles.
    | Each taxonomy maps to a relation on the Article model.
    |
    | Configuration options:
    | - label: Display label for the taxonomy
    | - relation: The relationship name on the Article model
    | - model: The taxonomy model class
    | - hierarchical: Whether the taxonomy supports parent-child relationships
    |
    */

    'taxonomies' => [
        'category' => [
            'label' => 'Category',
            'relation' => 'category',
            'model' => \App\Models\Category::class,
            'hierarchical' => true,
        ],
        'post_tag' => [
            'label' => 'Tags',
            'relation' => 'tags',
            'model' => \App\Models\Tag::class,
            'hierarchical' => false,
        ],
        'post_tone' => [
            'label' => 'Tone',
            'relation' => 'tone',
            'model' => \App\Models\Tone::class,
            'hierarchical' => false,
        ],
        'post_campaign' => [
            'label' => 'Campaign',
            'relation' => 'campaign',
            'model' => \App\Models\Campaign::class,
            'hierarchical' => false,
        ],
        'opm_news_type' => [
            'label' => 'News Type',
            'relation' => 'tags',
            'model' => \App\Models\Tag::class,
            'hierarchical' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Rendering Options
    |--------------------------------------------------------------------------
    |
    | Global settings for template rendering behavior.
    |
    */

    'rendering' => [
        // Enable HTML escaping by default for security
        'escape_by_default' => true,

        // HTML-safe fields that should not be escaped
        'html_safe_fields' => [
            'post_content',
            'post_excerpt',
            'body',
            'excerpt',
        ],

        // Maximum body excerpt length
        'default_excerpt_length' => 200,

        // Date format for exports
        'default_date_format' => 'Y-m-d H:i:s',

        // Enable logging for template rendering
        'enable_logging' => env('TEMPLATE_LOGGING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholder Definitions
    |--------------------------------------------------------------------------
    |
    | Defines all available placeholders for use in simple templates.
    | Used for validation and documentation purposes.
    |
    */

    'placeholders' => [
        'article' => [
            'title' => 'Article title',
            'body' => 'Full article body',
            'excerpt' => 'Article excerpt',
            'author' => 'Author name',
            'category' => 'Category name',
            'tags' => 'Comma-separated tags',
            'tags_list' => 'Formatted tags list',
            'article_index' => 'Sequential article number',
            'approved_at' => 'Approval date and time',
            'approved_date' => 'Approval date only',
            'published_at' => 'Publication date and time',
            'source_url' => 'Source URL',
            'source' => 'Source outlet name',
            'tone' => 'Tone label',
            'title_uppercase' => 'Uppercase title',
        ],
        'global' => [
            'export_date' => 'Export date',
            'total_articles' => 'Total article count',
            'approved_from' => 'Filter start date',
            'approved_to' => 'Filter end date',
            'category_group' => 'Auto-group articles by category',
            'source_name' => 'Source label for tagged formats',
        ],
        'blocks' => [
            '#articles' => 'Repeat per article',
            '#body_paragraphs' => 'Split body into paragraphs',
            '#if' => 'Conditional block',
            '#group_by_category' => 'Category grouping block',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Rules for validating template syntax and structure.
    |
    */

    'validation' => [
        // Require article block in templates
        'require_article_block' => true,

        // Maximum template size in characters
        'max_template_size' => 50000,

        // Block tags that require closing tags
        'paired_blocks' => [
            'articles',
            'body_paragraphs',
            'if',
            'group_by_category',
        ],
    ],
];
