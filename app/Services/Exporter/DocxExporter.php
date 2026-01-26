<?php

namespace App\Services\Exporter;

use App\Models\BulletinExport;
use App\Models\ExportTemplate;
use App\Models\Article;
use App\Services\Shortcodes\ShortcodeTemplateRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class DocxExporter
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
        } else {
            $renderer = new TemplateRenderer();
            $html = $renderer->render(
                $template->html_body ?? '',
                $articles,
                $this->buildContext($export, $template, $articles),
                false
            );
        }

        $html = $this->normalizeHtml($this->sanitizeHtml($html));

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        if ($html !== '') {
            Html::addHtml($section, $html, false, false);
        }

        $filename = 'bulletin_' . date('Y-m-d_His') . '.docx';
        $path = 'exports/' . $filename;

        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::disk('local')->path($path);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        $export->update([
            'output_file_path' => $path,
            'articles_count' => $articles->count(),
            'output_format' => 'docx',
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

    protected function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = preg_replace('/<\/?(thead|tbody|tfoot|colgroup)[^>]*>/i', '', $html) ?? $html;
        $html = str_ireplace(['<th', '</th>'], ['<td', '</td>'], $html);
        $html = str_ireplace(['<br>', '<hr>'], ['<br />', '<hr />'], $html);

        return $html;
    }

    protected function normalizeHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"?><html><body>' . $html . '</body></html>');
        $body = $dom->getElementsByTagName('body')->item(0);

        $normalized = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $normalized .= $dom->saveXML($child);
            }
        }

        libxml_clear_errors();

        return $normalized ?? $html;
    }
}
