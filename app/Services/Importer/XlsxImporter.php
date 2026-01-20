<?php

namespace App\Services\Importer;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class XlsxImporter implements ImporterInterface
{
    protected array $columnMap = [
        'title' => ['title', 'headline', 'name', 'article_title'],
        'body' => ['body', 'content', 'text', 'article_body', 'description'],
        'category' => ['category', 'cat', 'section', 'type'],
        'date' => ['date', 'published_date', 'publish_date', 'created_date'],
    ];

    public function parse(string $filePath): Collection
    {
        $articles = collect();

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return $articles;
            }

            // First row is header
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            $mapping = $this->mapColumns($headers);

            // Process data rows
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $article = $this->parseRow($row, $mapping);

                if (!empty($article['title']) && !empty($article['body'])) {
                    $articles->push($article);
                }
            }
        } catch (\Exception $e) {
            Log::error('XLSX parsing error', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $articles;
    }

    public function supports(string $fileType): bool
    {
        return in_array(strtolower($fileType), ['xlsx', 'xls']);
    }

    protected function mapColumns(array $headers): array
    {
        $mapping = [];

        foreach ($this->columnMap as $field => $possibleNames) {
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers);
                if ($index !== false) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }

        // Fallback: if no mapping found, assume first columns are title, body
        if (!isset($mapping['title']) && count($headers) > 0) {
            $mapping['title'] = 0;
        }
        if (!isset($mapping['body']) && count($headers) > 1) {
            $mapping['body'] = 1;
        }

        return $mapping;
    }

    protected function parseRow(array $row, array $mapping): array
    {
        $getValue = function ($field) use ($row, $mapping) {
            if (!isset($mapping[$field])) {
                return null;
            }
            $index = $mapping[$field];
            return isset($row[$index]) ? trim($row[$index]) : null;
        };

        $body = $getValue('body');
        if ($body && !str_starts_with($body, '<')) {
            // Wrap plain text in paragraphs
            $body = '<p>' . nl2br(htmlspecialchars($body)) . '</p>';
        }

        $date = $getValue('date');
        if ($date) {
            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {
                $date = null;
            }
        }

        return [
            'title' => $getValue('title'),
            'body' => $body,
            'category' => $getValue('category'),
            'date' => $date,
        ];
    }
}
