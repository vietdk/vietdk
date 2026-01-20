<?php

namespace App\Services\Exporter;

use App\Models\Article;
use App\Models\BulletinExport;
use App\Models\ExportTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Font;

class DocxExporter
{
    public function export(BulletinExport $export, Collection $articles): string
    {
        $phpWord = new PhpWord();

        // Define styles
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 24], ['spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 16], ['spaceBefore' => 240, 'spaceAfter' => 120]);

        $section = $phpWord->addSection();

        // Add bulletin header
        $this->addBulletinHeader($section, $export);

        // Add each article
        foreach ($articles as $index => $article) {
            if ($index > 0) {
                $section->addPageBreak();
            }
            $this->addArticle($section, $article, $index + 1);
        }

        // Save the document
        $filename = 'bulletin_' . date('Y-m-d_His') . '.docx';
        $path = 'exports/' . $filename;

        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::disk('local')->path($path);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        // Update export record
        $export->update([
            'output_file_path' => $path,
            'articles_count' => $articles->count(),
        ]);

        return $path;
    }

    protected function addBulletinHeader($section, BulletinExport $export): void
    {
        $section->addTitle('News Bulletin', 1);

        $textRun = $section->addTextRun();
        $textRun->addText('Generated: ', ['bold' => true]);
        $textRun->addText(now()->format('F j, Y'));

        if ($export->filters) {
            $dateRange = $export->getDateRange();
            if ($dateRange['from'] || $dateRange['to']) {
                $section->addTextBreak();
                $textRun = $section->addTextRun();
                $textRun->addText('Date Range: ', ['bold' => true]);

                $from = $dateRange['from'] ? date('M j, Y', strtotime($dateRange['from'])) : 'Start';
                $to = $dateRange['to'] ? date('M j, Y', strtotime($dateRange['to'])) : 'Present';
                $textRun->addText("{$from} - {$to}");
            }
        }

        $section->addTextBreak();
        $textRun = $section->addTextRun();
        $textRun->addText('Total Articles: ', ['bold' => true]);
        $textRun->addText((string) $export->articles_count);

        $section->addTextBreak(2);
        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0]);
        $section->addTextBreak();
    }

    protected function addArticle($section, Article $article, int $number): void
    {
        // Article number and title
        $section->addTitle("{$number}. {$article->title}", 2);

        // Metadata
        $textRun = $section->addTextRun(['spaceAfter' => 120]);

        if ($article->category) {
            $textRun->addText('Category: ', ['bold' => true, 'size' => 10]);
            $textRun->addText($article->category->name . '  ', ['size' => 10]);
        }

        if ($article->published_at) {
            $textRun->addText('Published: ', ['bold' => true, 'size' => 10]);
            $textRun->addText($article->published_at->format('M j, Y') . '  ', ['size' => 10]);
        }

        $textRun->addText('Author: ', ['bold' => true, 'size' => 10]);
        $textRun->addText($article->author->name ?? 'Unknown', ['size' => 10]);

        $section->addTextBreak();

        // Article body
        $this->addHtmlContent($section, $article->body);

        // Tags
        if ($article->tags->isNotEmpty()) {
            $section->addTextBreak();
            $textRun = $section->addTextRun();
            $textRun->addText('Tags: ', ['bold' => true, 'size' => 9, 'italic' => true]);
            $textRun->addText($article->tags->pluck('name')->join(', '), ['size' => 9, 'italic' => true]);
        }

        // Source reference
        if ($article->sourceMetadata) {
            $section->addTextBreak();
            $textRun = $section->addTextRun();
            $textRun->addText('Source: ', ['bold' => true, 'size' => 9]);
            $section->addLink($article->sourceMetadata->url, $article->sourceMetadata->title, ['size' => 9, 'color' => '0066CC']);
        }
    }

    protected function addHtmlContent($section, string $html): void
    {
        // Clean and convert HTML to plain text with basic formatting
        $html = $this->cleanHtml($html);

        // Try to use PHPWord's HTML converter
        try {
            Html::addHtml($section, $html, false, false);
        } catch (\Exception $e) {
            // Fallback: add as plain text
            $plainText = strip_tags($html);
            $paragraphs = preg_split('/\n\n+/', $plainText);

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $section->addText($paragraph, [], ['spaceAfter' => 120]);
                }
            }
        }
    }

    protected function cleanHtml(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Ensure proper paragraph wrapping
        if (!preg_match('/<p[^>]*>/i', $html)) {
            $html = '<p>' . $html . '</p>';
        }

        return $html;
    }
}
