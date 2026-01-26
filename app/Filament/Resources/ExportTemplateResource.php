<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExportTemplateResource\Pages;
use App\Models\Article;
use App\Models\Category;
use App\Models\ExportTemplate;
use App\Models\Tag;
use App\Services\Shortcodes\ShortcodeArgsCodec;
use App\Services\Shortcodes\TaxonomyRegistry;
use App\Services\TemplateValidation\SimpleTemplateValidator;
use App\Services\TemplateValidation\ShortcodeTemplateValidator;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ExportTemplateResource extends Resource
{
    protected static ?string $model = ExportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Import/Export';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Export Templates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Use a descriptive name like "Client Name - Format Type" (e.g., "AES Energy - Table Format")'),

                Forms\Components\Hidden::make('file_path')
                    ->default('inline'),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->helperText('Describe the template purpose, target client, and key formatting features.')
                    ->columnSpanFull(),

                Forms\Components\Section::make('Placeholder Examples')
                    ->schema([
                        Forms\Components\Placeholder::make('placeholder_examples')
                            ->content(fn () => view('filament.components.template-placeholder-reference', [
                                'placeholders' => self::getPlaceholderReference(),
                                'compact' => true,
                            ]))
                            ->columnSpanFull(),
                        Forms\Components\Actions::make([
                            FormAction::make('preview_placeholders')
                                ->label('Preview Placeholders')
                                ->icon('heroicon-o-eye')
                                ->modalHeading('Available Placeholders')
                                ->modalContent(fn () => view('filament.components.template-placeholder-reference', [
                                    'placeholders' => self::getPlaceholderReference(),
                                    'compact' => false,
                                ])),
                        ]),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Available Variables')
                    ->schema([
                        Forms\Components\Placeholder::make('variables_panel')
                            ->content(fn () => view('filament.components.template-variables-panel', [
                                'placeholders' => self::getPlaceholderReference(),
                            ]))
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn (string $operation) => $operation === 'create'),

                Forms\Components\Section::make('Template Content')
                    ->schema([
                        Forms\Components\Select::make('template_type')
                            ->label('Template Type')
                            ->options([
                                'simple' => 'Simple (Mustache) - Recommended for most bulletins',
                                'shortcode' => 'Shortcode (Advanced) - For complex dynamic filtering',
                            ])
                            ->helperText('Simple templates use {{placeholder}} syntax and work for most use cases. Shortcode templates offer advanced filtering but are more complex.')
                            ->default('simple')
                            ->reactive(),

                        Forms\Components\Textarea::make('html_body')
                            ->label('HTML Template')
                            ->helperText('Use {{#articles}} ... {{/articles}} to repeat per article. Supports tables, formatting, and grouping blocks like {{#group_by_category}}. HTML renders in DOCX exports - keep HTML simple for best Word compatibility. See "Placeholder Examples" above for all available variables.')
                            ->rows(12)
                            ->columnSpanFull()
                            ->required(fn (Forms\Get $get) => $get('template_type') === 'simple')
                            ->visible(fn (Forms\Get $get) => $get('template_type') === 'simple')
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        if ($get('template_type') === 'simple' && $value) {
                                            $validator = new SimpleTemplateValidator();
                                            $result = $validator->validate($value, 'html');
                                            if (!$result->isValid) {
                                                $fail($result->getErrorMessages());
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Textarea::make('text_body')
                            ->label('Text Template')
                            ->rows(10)
                            ->helperText('Plain text template for TXT export. Use {{placeholder}} syntax for variables. Supports tagged formats like [SO], [DD], [HH], [QQ] for Vietnam News Brief format. Use {{#group_by_category}} for organizing by categories. See "Placeholder Examples" above.')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('template_type') === 'simple')
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        if ($get('template_type') === 'simple' && $value) {
                                            $validator = new SimpleTemplateValidator();
                                            $result = $validator->validate($value, 'text');
                                            if (!$result->isValid) {
                                                $fail($result->getErrorMessages());
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Textarea::make('shortcode_body')
                            ->label('Shortcode Template')
                            ->rows(14)
                            ->helperText('Use [list_posts args="..."] with [loop] blocks for shortcode templates.')
                            ->columnSpanFull()
                            ->required(fn (Forms\Get $get) => $get('template_type') === 'shortcode')
                            ->visible(fn (Forms\Get $get) => $get('template_type') === 'shortcode')
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        if ($get('template_type') === 'shortcode' && $value) {
                                            $validator = new ShortcodeTemplateValidator();
                                            $result = $validator->validate($value, 'shortcode');
                                            if (!$result->isValid) {
                                                $fail($result->getErrorMessages());
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Section::make('List Posts Args Builder')
                            ->schema([
                                Forms\Components\Select::make('shortcode_relation')
                                    ->label('Relation')
                                    ->options([
                                        'AND' => 'AND',
                                        'OR' => 'OR',
                                    ])
                                    ->default('AND')
                                    ->reactive()
                                    ->dehydrated(false),
                                Forms\Components\Repeater::make('shortcode_filters')
                                    ->label('Taxonomy Filters')
                                    ->schema([
                                        Forms\Components\Select::make('taxonomy')
                                            ->label('Taxonomy')
                                            ->options(TaxonomyRegistry::options())
                                            ->required(),
                                        Forms\Components\Select::make('field')
                                            ->label('Field')
                                            ->options([
                                                'id' => 'ID',
                                                'slug' => 'Slug',
                                                'name' => 'Name',
                                            ])
                                            ->default('id')
                                            ->required(),
                                        Forms\Components\Select::make('operator')
                                            ->label('Operator')
                                            ->options([
                                                'IN' => 'IN',
                                                'NOT IN' => 'NOT IN',
                                            ])
                                            ->default('IN')
                                            ->required(),
                                        Forms\Components\TagsInput::make('terms')
                                            ->label('Terms')
                                            ->placeholder('e.g. 123, 456 or slug values')
                                            ->required(),
                                        Forms\Components\Toggle::make('children')
                                            ->label('Include Children')
                                            ->default(true),
                                    ])
                                    ->columns(5)
                                    ->reactive()
                                    ->dehydrated(false),
                                Forms\Components\Textarea::make('shortcode_json')
                                    ->label('Args JSON')
                                    ->rows(6)
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->formatStateUsing(function (Forms\Get $get): string {
                                        return json_encode(self::buildShortcodeArgs($get), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                    }),
                                Forms\Components\Textarea::make('shortcode_base64')
                                    ->label('Args Base64')
                                    ->rows(4)
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->formatStateUsing(function (Forms\Get $get): string {
                                        $codec = new ShortcodeArgsCodec();
                                        return $codec->encode(self::buildShortcodeArgs($get));
                                    }),
                                Forms\Components\Textarea::make('shortcode_paste')
                                    ->label('Paste Base64 to Load')
                                    ->rows(3)
                                    ->dehydrated(false)
                                    ->reactive()
                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                        if (!$state) {
                                            return;
                                        }

                                        $codec = new ShortcodeArgsCodec();
                                        $decoded = $codec->decode($state);
                                        $filters = [];
                                        foreach (($decoded['tax_query'] ?? []) as $filter) {
                                            if (!is_array($filter) || !isset($filter['taxonomy'])) {
                                                continue;
                                            }

                                            $filters[] = [
                                                'taxonomy' => $filter['taxonomy'] ?? null,
                                                'field' => $filter['field'] ?? 'id',
                                                'operator' => $filter['operator'] ?? 'IN',
                                                'terms' => $filter['terms'] ?? [],
                                                'children' => ($filter['children'] ?? 'true') === 'true',
                                            ];
                                        }

                                        $set('shortcode_relation', $decoded['tax_query']['relation'] ?? 'AND');
                                        $set('shortcode_filters', $filters);
                                    }),
                                Forms\Components\Actions::make([
                                    FormAction::make('insert_list_posts')
                                        ->label('Insert list_posts block')
                                        ->action(function (Forms\Get $get, Set $set): void {
                                            $codec = new ShortcodeArgsCodec();
                                            $base64 = $codec->encode(self::buildShortcodeArgs($get));
                                            $snippet = "[list_posts args=\"{$base64}\"]\n[loop do_shortcode_first=\"false\"]\n\n[/loop]\n[/list_posts]";
                                            $current = (string) $get('shortcode_body');
                                            $set('shortcode_body', trim($current . "\n\n" . $snippet));
                                        }),
                                ]),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('template_type') === 'shortcode'),
                    ]),

                Forms\Components\Section::make('Template Filters')
                    ->schema([
                        Forms\Components\DatePicker::make('filters.approved_from')
                            ->label('Approved From'),

                        Forms\Components\DatePicker::make('filters.approved_to')
                            ->label('Approved To'),

                        Forms\Components\Select::make('filters.category_ids')
                            ->label('Categories')
                            ->options(Category::pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),

                        Forms\Components\Select::make('filters.tag_ids')
                            ->label('Tags')
                            ->options(Tag::pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),

                        Forms\Components\Select::make('filters.status')
                            ->label('Article Status')
                            ->options([
                                Article::STATUS_APPROVED => 'Approved',
                                Article::STATUS_PUBLISHED => 'Published',
                                'all' => 'All Statuses',
                            ])
                            ->default(Article::STATUS_APPROVED),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Grouping Options')
                    ->schema([
                        Forms\Components\Select::make('grouping_type')
                            ->label('Grouping Type')
                            ->options([
                                'none' => 'None',
                                'category' => 'Category',
                                'tag' => 'Tag',
                                'custom' => 'Custom',
                            ])
                            ->default('none'),
                        Forms\Components\Toggle::make('show_group_headers')
                            ->label('Show Group Headers')
                            ->default(true),
                        Forms\Components\TextInput::make('group_header_format')
                            ->label('Group Header Format')
                            ->helperText('Use {{group_name}} to insert the category or tag name.')
                            ->default('=== {{group_name}} ==='),
                        Forms\Components\TagsInput::make('grouping_order')
                            ->label('Custom Group Order')
                            ->helperText('Order labels to control grouping sequence.')
                            ->placeholder('Energy, Policy, Investment'),
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('is_default')
                    ->label('Set as Default Template'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),

                Tables\Columns\TextColumn::make('bulletin_exports_count')
                    ->counts('bulletinExports')
                    ->label('Uses'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->state(fn (ExportTemplate $record) => $record->bulletinExports()->latest('created_at')->value('created_at'))
                    ->dateTime(),

                Tables\Columns\TextColumn::make('average_articles')
                    ->label('Avg Articles')
                    ->state(function (ExportTemplate $record) {
                        $average = $record->bulletinExports()->avg('articles_count');
                        return $average ? number_format((float) $average, 1) : '0.0';
                    }),

                Tables\Columns\TextColumn::make('common_filters')
                    ->label('Common Filters')
                    ->state(fn (ExportTemplate $record) => self::summarizeCommonFilters($record))
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (ExportTemplate $record): void {
                        $copy = $record->replicate();
                        $copy->name = $record->name . ' (Copy)';
                        $copy->is_default = false;
                        $copy->push();

                        Notification::make()
                            ->title('Template duplicated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('set_default')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (ExportTemplate $record) {
                        $record->setAsDefault();
                        Notification::make()
                            ->title('Template set as default')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ExportTemplate $record) => !$record->is_default),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExportTemplates::route('/'),
            'create' => Pages\CreateExportTemplate::route('/create'),
            'edit' => Pages\EditExportTemplate::route('/{record}/edit'),
        ];
    }

    protected static function getPlaceholderReference(): array
    {
        return [
            'Article' => [
                ['{{title}}', 'Article title'],
                ['{{body}}', 'Full article body'],
                ['{{excerpt}}', 'Article excerpt'],
                ['{{author}}', 'Author name'],
                ['{{category}}', 'Category name'],
                ['{{tags}}', 'Comma-separated tags'],
                ['{{tags_list}}', 'Formatted tags list'],
                ['{{article_index}}', 'Sequential article number'],
                ['{{approved_at}}', 'Approval date and time'],
                ['{{approved_date}}', 'Approval date only'],
                ['{{published_at}}', 'Publication date and time'],
                ['{{source_url}}', 'Source URL'],
                ['{{source}}', 'Source outlet name'],
                ['{{tone}}', 'Tone label'],
                ['{{title_uppercase}}', 'Uppercase title'],
                ['{{body_excerpt|200}}', 'Body truncated to 200 characters'],
            ],
            'Global' => [
                ['{{export_date}}', 'Export date'],
                ['{{total_articles}}', 'Total article count'],
                ['{{approved_from}}', 'Filter start date'],
                ['{{approved_to}}', 'Filter end date'],
                ['{{category_group}}', 'Auto-group articles by category'],
                ['{{source_name}}', 'Source label for tagged formats'],
            ],
            'Blocks' => [
                ['{{#articles}}...{{/articles}}', 'Repeat per article'],
                ['{{#body_paragraphs}}...{{/body_paragraphs}}', 'Split body into paragraphs'],
                ['{{#if category}}...{{/if}}', 'Conditional block'],
                ['{{#group_by_category}}...{{/group_by_category}}', 'Category grouping block'],
            ],
        ];
    }

    protected static function summarizeCommonFilters(ExportTemplate $record): string
    {
        $exports = $record->bulletinExports()->get();
        if ($exports->isEmpty()) {
            return 'No exports yet';
        }

        [$categoryCounts, $tagCounts] = self::accumulateFilterCounts($exports);
        $categoryNames = self::topFilterNames($categoryCounts, Category::class);
        $tagNames = self::topFilterNames($tagCounts, Tag::class);

        $parts = [];
        if (!empty($categoryNames)) {
            $parts[] = 'Categories: ' . implode(', ', $categoryNames);
        }
        if (!empty($tagNames)) {
            $parts[] = 'Tags: ' . implode(', ', $tagNames);
        }

        return $parts ? implode(' | ', $parts) : 'No filters tracked';
    }

    protected static function accumulateFilterCounts(Collection $exports): array
    {
        $categoryCounts = [];
        $tagCounts = [];

        foreach ($exports as $export) {
            foreach ($export->getCategoryIds() as $categoryId) {
                $categoryCounts[$categoryId] = ($categoryCounts[$categoryId] ?? 0) + 1;
            }
            foreach ($export->getTagIds() as $tagId) {
                $tagCounts[$tagId] = ($tagCounts[$tagId] ?? 0) + 1;
            }
        }

        return [$categoryCounts, $tagCounts];
    }

    protected static function topFilterNames(array $counts, string $modelClass): array
    {
        if (empty($counts)) {
            return [];
        }

        arsort($counts);
        $topIds = array_slice(array_keys($counts), 0, 3);
        $names = $modelClass::query()->whereIn('id', $topIds)->pluck('name', 'id');

        return collect($topIds)
            ->map(fn ($id) => $names[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    protected static function buildShortcodeArgs(Get $get): array
    {
        $filters = $get('shortcode_filters') ?? [];
        $relation = $get('shortcode_relation') ?? 'AND';
        $args = [
            'tax_query' => [
                'relation' => $relation,
            ],
            'fields' => 'ids',
        ];

        foreach ($filters as $filter) {
            if (!is_array($filter) || empty($filter['taxonomy'])) {
                continue;
            }

            $args['tax_query'][] = [
                'taxonomy' => $filter['taxonomy'],
                'field' => $filter['field'] ?? 'id',
                'operator' => $filter['operator'] ?? 'IN',
                'terms' => $filter['terms'] ?? [],
                'children' => !empty($filter['children']) ? 'true' : 'false',
            ];
        }

        return $args;
    }
}
