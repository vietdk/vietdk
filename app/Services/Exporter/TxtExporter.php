<?php

namespace App\Services\Exporter;

use App\Models\BulletinExport;
use App\Models\ExportTemplate;
use App\Models\Article;
use App\Services\Shortcodes\ShortcodeTemplateRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class TxtExporter
{
    public function export(BulletinExport $export, ExportTemplate $template, Collection $articles): string
    {
        if ($template->template_type === 'shortcode') {
            $renderer = new ShortcodeTemplateRenderer();
            $html = $renderer->render(
                $template->shortcode_body ?? '',
                $this->buildBaseQuery($articles),
                $this->buildContext($export, $template, $articles)
            );
            $text = $this->htmlToText($html);
        } else {
            $renderer = new TemplateRenderer();
            $text = $renderer->render(
                $template->text_body ?? '',
                $articles,
                $this->buildContext($export, $template, $articles),
                true
            );
        }

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

    protected function buildContext(BulletinExport $export, ExportTemplate $template, Collection $articles): array
    {
        $dateRange = $export->getDateRange() ?? [];

        return [
            'export_date' => now(),
            'total_articles' => $articles->count(),
            'approved_from' => $dateRange['from'] ?? '',
            'approved_to' => $dateRange['to'] ?? '',
            'show_group_headers' => $template->show_group_headers,
            'group_header_format' => $template->group_header_format,
        ];
    }

    protected function buildBaseQuery(Collection $articles): \Illuminate\Database\Eloquent\Builder
    {
        return Article::query()
            ->with(['category', 'tags', 'tone', 'campaign', 'sourceMetadata'])
            ->whereIn('id', $articles->pluck('id'));
    }

    protected function htmlToText(string $html): string
    {
        $html = str_ireplace(['<br>', '<br />', '</p>', '</tr>', '</li>'], "\n", $html);
        return trim(strip_tags($html));
    }
}
