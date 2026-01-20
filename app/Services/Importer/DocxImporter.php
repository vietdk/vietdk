<?php

namespace App\Services\Importer;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;

class DocxImporter implements ImporterInterface
{
    public function parse(string $filePath): Collection
    {
        $articles = collect();

        try {
            $phpWord = IOFactory::load($filePath);
            $article = $this->extractArticle($phpWord);

            if (!empty($article['title']) && !empty($article['body'])) {
                $articles->push($article);
            }
        } catch (\Exception $e) {
            Log::error('DOCX parsing error', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $articles;
    }

    public function supports(string $fileType): bool
    {
        return strtolower($fileType) === 'docx';
    }

    protected function extractArticle($phpWord): array
    {
        $title = '';
        $body = '';
        $category = null;
        $foundTitle = false;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text = $this->getElementText($element);

                // First heading or non-empty paragraph is the title
                if (!$foundTitle && !empty(trim($text))) {
                    if ($element instanceof Title) {
                        $title = trim($text);
                        $foundTitle = true;
                        continue;
                    }
                    // If no Title element, use first significant text
                    if (strlen(trim($text)) > 10 && strlen(trim($text)) < 200) {
                        $title = trim($text);
                        $foundTitle = true;
                        continue;
                    }
                }

                // Look for category marker
                if (preg_match('/^Category:\s*(.+)$/i', trim($text), $matches)) {
                    $category = trim($matches[1]);
                    continue;
                }

                // Everything else is body content
                if ($foundTitle && !empty(trim($text))) {
                    $body .= '<p>' . htmlspecialchars(trim($text)) . '</p>';
                }
            }
        }

        return [
            'title' => $title,
            'body' => $body,
            'category' => $category,
            'date' => null,
        ];
    }

    protected function getElementText($element): string
    {
        if ($element instanceof Text) {
            return $element->getText();
        }

        if ($element instanceof TextRun) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= $this->getElementText($child);
            }
            return $text;
        }

        if ($element instanceof Title) {
            return $this->getElementText($element->getText());
        }

        if (method_exists($element, 'getText')) {
            $textContent = $element->getText();
            if (is_string($textContent)) {
                return $textContent;
            }
            if (is_object($textContent)) {
                return $this->getElementText($textContent);
            }
        }

        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= $this->getElementText($child) . ' ';
            }
            return $text;
        }

        return '';
    }
}
