<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessImport;
use App\Models\Import;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportArticles extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.pages.import-articles';

    protected static ?string $navigationGroup = 'Import/Export';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Import Articles';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Upload File')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Select File')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required()
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('imports')
                            ->helperText('Accepted formats: DOCX, XLSX (max 10MB)'),

                        Forms\Components\Placeholder::make('instructions')
                            ->label('File Format Instructions')
                            ->content(view('filament.components.import-instructions')),
                    ]),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();

        if (empty($data['file'])) {
            Notification::make()
                ->title('Please select a file')
                ->danger()
                ->send();
            return;
        }

        $filePath = $data['file'];
        $filename = basename($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $fileType = match ($extension) {
            'docx' => Import::TYPE_DOCX,
            'xlsx', 'xls' => Import::TYPE_XLSX,
            default => null,
        };

        if (!$fileType) {
            Notification::make()
                ->title('Unsupported file type')
                ->danger()
                ->send();
            return;
        }

        $import = Import::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'file_type' => $fileType,
            'file_path' => $filePath,
            'status' => Import::STATUS_PENDING,
        ]);

        ProcessImport::dispatch($import);

        Notification::make()
            ->title('Import started')
            ->body("Processing {$filename}. Check import history for results.")
            ->success()
            ->send();

        $this->form->fill();

        $this->redirect(route('filament.admin.resources.imports.index'));
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('import')
                ->label('Start Import')
                ->submit('import'),
        ];
    }
}
