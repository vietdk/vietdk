<?php

namespace App\Services\Exporter;

use App\Models\BulletinExport;
use App\Models\ExportTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class TxtExporter
{
    public function export(BulletinExport $export, ExportTemplate $template, Collection $articles): string
    {
        $renderer = new TemplateRenderer();
        $text = $renderer->render(
            $template->text_body ?? '',
            $articles,
            $this->buildContext($export, $articles),
            true
        );

        $filename = 'bulletin_' . date('Y-m-d_His') . '.txt';
        $path = 'exports/' . $filename;

        Storage::disk('local')->makeDirectory('exports');
        Storage::disk('local')->put($path, $text);

        $export->update([
            'output_file_path' => $path,
            'articles_count' => $articles->count(),
            'output_format' => 'txt',
        ]);

        return $path;
    }

    protected function buildContext(BulletinExport $export, Collection $articles): array
    {
        $dateRange = $export->getDateRange() ?? [];

        return [
            'export_date' => now()->format('Y-m-d'),
            'total_articles' => $articles->count(),
            'approved_from' => $dateRange['from'] ?? '',
            'approved_to' => $dateRange['to'] ?? '',
        ];
    }
}
