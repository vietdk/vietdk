<?php

namespace App\Filament\Pages;

use App\Models\BulletinExport;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadExport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string $view = 'filament.pages.download-export';

    protected static bool $shouldRegisterNavigation = false;

    public ?BulletinExport $export = null;

    public function mount(?int $export = null): void
    {
        $exportId = $export ?? request()->query('export');

        if ($exportId) {
            $this->export = BulletinExport::find((int) $exportId);
        }
    }

    public function download(): StreamedResponse
    {
        if (!$this->export || !$this->export->output_file_path) {
            abort(404, 'Export file not found');
        }

        $path = $this->export->output_file_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Export file not found');
        }

        $extension = $this->export->output_format === 'txt' ? 'txt' : 'docx';
        $filename = 'bulletin_' . $this->export->created_at->format('Y-m-d') . '.' . $extension;

        return Storage::disk('local')->download($path, $filename);
    }
}
