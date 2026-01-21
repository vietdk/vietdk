<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\BulletinExport;
use App\Models\ExportTemplate;
use App\Services\Exporter\DocxExporter;
use App\Services\Exporter\TxtExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
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
                            ->afterStateUpdated(fn () => $this->data['article_ids'] = []),

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

                Forms\Components\Section::make('Preview')
                    ->schema([
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
        $selectedIds = $this->data['article_ids'] ?? [];
        if (empty($selectedIds)) {
            return collect();
        }

        return $this->buildQuery()
            ->whereIn('id', $selectedIds)
            ->limit(10)
            ->get();
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

    protected function buildQuery()
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

        return $query->orderBy('approved_at', 'desc');
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

        $selectedIds = $this->data['article_ids'] ?? [];
        if (empty($selectedIds)) {
            Notification::make()
                ->title('No articles selected')
                ->body('Select at least one article to export.')
                ->warning()
                ->send();
            return;
        }

        $articles = $this->buildQuery()->whereIn('id', $selectedIds)->get();

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
}
