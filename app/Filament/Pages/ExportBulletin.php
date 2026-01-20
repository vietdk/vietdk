<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\BulletinExport;
use App\Models\Category;
use App\Models\Tag;
use App\Services\Exporter\DocxExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
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
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filter Articles')
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date'),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date'),

                        Forms\Components\Select::make('category_ids')
                            ->label('Categories')
                            ->options(Category::pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),

                        Forms\Components\Select::make('tag_ids')
                            ->label('Tags')
                            ->options(Tag::pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),

                        Forms\Components\Select::make('status')
                            ->label('Article Status')
                            ->options([
                                Article::STATUS_PUBLISHED => 'Published Only',
                                Article::STATUS_APPROVED => 'Approved',
                                'all' => 'All Statuses',
                            ])
                            ->default(Article::STATUS_PUBLISHED),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Manual Selection')
                    ->schema([
                        Forms\Components\Select::make('article_ids')
                            ->label('Select Specific Articles')
                            ->options(function () {
                                return Article::published()
                                    ->orderBy('published_at', 'desc')
                                    ->limit(100)
                                    ->pluck('title', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->helperText('Leave empty to use filters above, or select specific articles'),
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
    public function getPreviewArticles()
    {
        return $this->buildQuery()->limit(10)->get();
    }

    protected function buildQuery()
    {
        $query = Article::query()->with(['category', 'author', 'tags']);

        $data = $this->data ?? [];

        // If specific articles are selected, use those
        if (!empty($data['article_ids'])) {
            return $query->whereIn('id', $data['article_ids'])
                ->orderBy('published_at', 'desc');
        }

        // Apply filters
        if (!empty($data['date_from'])) {
            $query->where('published_at', '>=', $data['date_from']);
        }

        if (!empty($data['date_to'])) {
            $query->where('published_at', '<=', $data['date_to'] . ' 23:59:59');
        }

        if (!empty($data['category_ids'])) {
            $query->whereIn('category_id', $data['category_ids']);
        }

        if (!empty($data['tag_ids'])) {
            $query->whereHas('tags', function ($q) use ($data) {
                $q->whereIn('tags.id', $data['tag_ids']);
            });
        }

        $status = $data['status'] ?? Article::STATUS_PUBLISHED;
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->orderBy('published_at', 'desc');
    }

    public function export(): void
    {
        $articles = $this->buildQuery()->get();

        if ($articles->isEmpty()) {
            Notification::make()
                ->title('No articles found')
                ->body('Please adjust your filters to include some articles.')
                ->warning()
                ->send();
            return;
        }

        $export = BulletinExport::create([
            'user_id' => auth()->id(),
            'template_id' => null,
            'filters' => $this->data,
        ]);

        try {
            $exporter = new DocxExporter();
            $path = $exporter->export($export, $articles);

            Notification::make()
                ->title('Bulletin exported successfully')
                ->body("Generated bulletin with {$articles->count()} articles.")
                ->success()
                ->send();

            // Redirect to download
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
