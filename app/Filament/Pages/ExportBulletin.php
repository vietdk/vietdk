<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\BulletinExport;
use App\Models\Category;
use App\Models\ExportTemplate;
use App\Models\Tag;
use App\Models\User;
use App\Services\Exporter\DocxExporter;
use App\Services\Exporter\TemplateRenderer;
use App\Services\Shortcodes\ShortcodeTemplateRenderer;
use App\Services\Exporter\TxtExporter;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class ExportBulletin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string $view = 'filament.pages.export-bulletin';

    protected static ?string $navigationGroup = 'Import/Export';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Export Bulletin';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'template_id' => ExportTemplate::getDefault()?->id,
            'output_format' => 'docx',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\Select::make('template_id')
                            ->label('Export Template')
                            ->options(ExportTemplate::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (): void {
                                $this->data['article_ids'] = [];
                                $this->data['ordered_article_ids'] = [];
                            }),

                        Forms\Components\Select::make('output_format')
                            ->label('Output Format')
                            ->options([
                                'docx' => 'DOCX',
                                'txt' => 'TXT',
                            ])
                            ->default('docx')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Eligible Articles')
                    ->schema([
                        Forms\Components\CheckboxList::make('article_ids')
                            ->label('Select Articles to Export')
                            ->options(fn () => $this->getEligibleArticleOptions())
                            ->helperText('Articles are filtered based on the selected template.')
                            ->columns(1)
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Article Order')
                    ->description('Optional custom ordering. Add articles and drag to reorder.')
                    ->schema([
                        Forms\Components\Repeater::make('ordered_article_ids')
                            ->label('Custom Order')
                            ->schema([
                                Forms\Components\Select::make('article_id')
                                    ->label('Article')
                                    ->options(fn () => $this->getEligibleArticleOptions())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->reorderable(),
                    ]),

                Forms\Components\Section::make('Preview')
                    ->schema([
                        Forms\Components\Actions::make([
                            FormAction::make('preview_template')
                                ->label('Preview Template')
                                ->icon('heroicon-o-eye')
                                ->modalHeading('Template Preview')
                                ->modalContent(fn () => new HtmlString($this->renderTemplatePreview())),
                        ]),
                        Forms\Components\Placeholder::make('preview')
                            ->label('')
                            ->content(fn () => view('filament.components.export-preview', ['articles' => $this->getPreviewArticles()])),
                    ]),
            ])
            ->statePath('data');
    }

    #[Computed]
    public function getPreviewArticles(): Collection
    {
        $selectedIds = $this->getSelectedArticleIds();
        if (empty($selectedIds)) {
            return collect();
        }

        $query = $this->buildQuery(!$this->hasCustomOrder())
            ->whereIn('id', $selectedIds)
            ->limit(10);

        if ($this->hasCustomOrder()) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $selectedIds) . ')');
        }

        return $query->get();
    }

    protected function getEligibleArticleOptions(): array
    {
        $articles = $this->buildQuery()->get();

        return $articles->mapWithKeys(function (Article $article) {
            $approvedAt = $article->approved_at?->format('Y-m-d H:i') ?? 'N/A';
            $label = "{$article->title} (Approved: {$approvedAt})";

            return [$article->id => $label];
        })->all();
    }

    protected function buildQuery(bool $applyOrdering = true)
    {
        $query = Article::query()->with(['category', 'author', 'tags', 'sourceMetadata']);

        $filters = $this->getTemplateFilters();

        if (!empty($filters['approved_from'])) {
            $query->where('approved_at', '>=', $filters['approved_from']);
        }

        if (!empty($filters['approved_to'])) {
            $query->where('approved_at', '<=', $filters['approved_to'] . ' 23:59:59');
        }

        if (!empty($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        if (!empty($filters['tag_ids'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', $filters['tag_ids']);
            });
        }

        $status = $filters['status'] ?? Article::STATUS_APPROVED;
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (!$applyOrdering) {
            return $query;
        }

        $template = $this->getTemplate();
        if ($template?->grouping_type === 'category') {
            $order = collect($template->grouping_order ?? [])
                ->map(fn ($value) => (int) $value)
                ->filter()
                ->values();

            if ($order->isNotEmpty()) {
                $query->orderByRaw('FIELD(category_id, ' . $order->implode(',') . ')');
            } else {
                $query->orderBy('category_id');
            }

            $query->orderBy('approved_at', 'desc');
        } else {
            $query->orderBy('approved_at', 'desc');
        }

        return $query;
    }

    protected function getTemplateFilters(): array
    {
        $template = $this->getTemplate();

        return $template?->filters ?? [];
    }

    protected function getTemplate(): ?ExportTemplate
    {
        $templateId = $this->data['template_id'] ?? null;

        if (!$templateId) {
            return ExportTemplate::getDefault();
        }

        return ExportTemplate::find($templateId);
    }

    protected function renderTemplatePreview(): string
    {
        $template = $this->getTemplate();
        if (!$template) {
            return '<p>Select a template to preview.</p>';
        }

        $outputFormat = $this->data['output_format'] ?? 'docx';
        if ($template->template_type === 'shortcode') {
            $body = $template->shortcode_body ?? '';
        } else {
            $body = $outputFormat === 'txt' ? ($template->text_body ?? '') : ($template->html_body ?? '');
        }

        if ($body === '') {
            return '<p>The selected template has no content.</p>';
        }

        $articles = $this->getPreviewArticles();
        if ($articles->isEmpty()) {
            $articles = $this->getSampleArticles();
        }

        if ($template->template_type === 'shortcode') {
            $renderer = new ShortcodeTemplateRenderer();
            $rendered = $renderer->render(
                $body,
                Article::query()
                    ->with(['category', 'tags', 'tone', 'campaign', 'sourceMetadata'])
                    ->whereIn('id', $articles->pluck('id')),
                $this->buildPreviewContext($articles, $template)
            );
        } else {
            $renderer = new TemplateRenderer();
            $rendered = $renderer->render(
                $body,
                $articles,
                $this->buildPreviewContext($articles, $template),
                $outputFormat === 'txt'
            );
        }

        if ($outputFormat === 'txt') {
            if ($template->template_type === 'shortcode') {
                $rendered = $this->htmlToText($rendered);
            }

            return '<pre class="whitespace-pre-wrap text-sm">' . e($rendered) . '</pre>';
        }

        return $rendered;
    }

    protected function buildPreviewContext(Collection $articles, ExportTemplate $template): array
    {
        return [
            'export_date' => now(),
            'total_articles' => $articles->count(),
            'show_group_headers' => $template->show_group_headers,
            'group_header_format' => $template->group_header_format,
        ];
    }

    protected function getSampleArticles(): Collection
    {
        $sample = Article::query()
            ->with(['category', 'author', 'tags', 'sourceMetadata'])
            ->latest('approved_at')
            ->limit(3)
            ->get();

        if ($sample->isNotEmpty()) {
            return $sample;
        }

        return collect([
            $this->makeSampleArticle(
                'AMRO Raises ASEAN+3 Growth Outlook',
                'Economic Indicators',
                'Sample body paragraph one.\n\nSample body paragraph two.',
                ['ASEAN', 'Growth']
            ),
            $this->makeSampleArticle(
                'Vietnam Credit May Grow 18% in 2026',
                'Banking',
                'Sample banking coverage with detailed notes.',
                ['Banking', 'Credit']
            ),
        ]);
    }

    protected function makeSampleArticle(string $title, string $categoryName, string $body, array $tags): Article
    {
        $article = new Article([
            'title' => $title,
            'body' => $body,
            'excerpt' => Str::limit(strip_tags($body), 120),
            'approved_at' => now(),
            'published_at' => now(),
            'status' => Article::STATUS_APPROVED,
        ]);

        $article->setRelation('category', new Category(['name' => $categoryName]));
        $article->setRelation('author', new User(['name' => 'Sample Editor']));
        $article->setRelation('tags', collect($tags)->map(fn ($tag) => new Tag(['name' => $tag])));

        return $article;
    }

    public function export(): void
    {
        $template = $this->getTemplate();

        if (!$template) {
            Notification::make()
                ->title('Please select a template')
                ->danger()
                ->send();
            return;
        }

        $selectedIds = $this->getSelectedArticleIds();
        if (empty($selectedIds)) {
            Notification::make()
                ->title('No articles selected')
                ->body('Select at least one article to export.')
                ->warning()
                ->send();
            return;
        }

        $query = $this->buildQuery(!$this->hasCustomOrder())->whereIn('id', $selectedIds);
        if ($this->hasCustomOrder()) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $selectedIds) . ')');
        }

        $articles = $query->get();

        if ($articles->isEmpty()) {
            Notification::make()
                ->title('No articles found')
                ->body('Please adjust your template filters or selections.')
                ->warning()
                ->send();
            return;
        }

        $outputFormat = $this->data['output_format'] ?? 'docx';

        $export = BulletinExport::create([
            'user_id' => auth()->id(),
            'template_id' => $template->id,
            'filters' => array_merge($this->getTemplateFilters(), [
                'article_ids' => $selectedIds,
            ]),
            'output_format' => $outputFormat,
        ]);

        try {
            if ($outputFormat === 'txt') {
                $exporter = new TxtExporter();
                $exporter->export($export, $template, $articles);
            } else {
                $exporter = new DocxExporter();
                $exporter->export($export, $template, $articles);
            }

            Notification::make()
                ->title('Bulletin exported successfully')
                ->body("Generated bulletin with {$articles->count()} articles.")
                ->success()
                ->send();

            $this->redirect(route('filament.admin.pages.download-export', ['export' => $export->id]));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getSelectedArticleIds(): array
    {
        $ordered = collect($this->data['ordered_article_ids'] ?? [])
            ->pluck('article_id')
            ->filter()
            ->values()
            ->all();

        if (!empty($ordered)) {
            return $ordered;
        }

        return $this->data['article_ids'] ?? [];
    }

    protected function hasCustomOrder(): bool
    {
        return !empty($this->data['ordered_article_ids'] ?? []);
    }

    protected function htmlToText(string $html): string
    {
        $html = str_ireplace(['<br>', '<br />', '</p>', '</tr>', '</li>'], "\n", $html);
        return trim(strip_tags($html));
    }
}
