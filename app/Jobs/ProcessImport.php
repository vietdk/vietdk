<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\Category;
use App\Models\Import;
use App\Services\Importer\DocxImporter;
use App\Services\Importer\XlsxImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public Import $import
    ) {}

    public function handle(DocxImporter $docxImporter, XlsxImporter $xlsxImporter): void
    {
        $this->import->markAsProcessing();

        try {
            $filePath = Storage::disk('local')->path($this->import->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$this->import->file_path}");
            }

            $importer = $this->import->file_type === Import::TYPE_DOCX
                ? $docxImporter
                : $xlsxImporter;

            $articles = $importer->parse($filePath);

            $createdCount = 0;
            foreach ($articles as $articleData) {
                $this->createArticle($articleData);
                $createdCount++;
            }

            $this->import->markAsCompleted($createdCount);

            Log::info('Import completed', [
                'import_id' => $this->import->id,
                'articles_created' => $createdCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->import->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function createArticle(array $data): Article
    {
        $categoryId = null;

        if (!empty($data['category'])) {
            $category = Category::where('name', $data['category'])
                ->orWhere('slug', $data['category'])
                ->first();

            if ($category) {
                $categoryId = $category->id;
            }
        }

        return Article::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'author_id' => $this->import->user_id,
            'category_id' => $categoryId,
            'status' => Article::STATUS_DRAFT,
            'published_at' => $data['date'] ?? null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->import->markAsFailed($exception->getMessage());

        Log::error('Import job failed permanently', [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
