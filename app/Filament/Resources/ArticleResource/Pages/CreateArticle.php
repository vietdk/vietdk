<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\CrawledMetadata;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    public function mount(): void
    {
        parent::mount();

        $sourceMetadataId = request()->query('source_metadata_id');

        if (!$sourceMetadataId) {
            return;
        }

        $metadata = CrawledMetadata::find($sourceMetadataId);

        if (!$metadata) {
            return;
        }

        $this->form->fill(array_merge($this->form->getState(), [
            'title' => $metadata->title,
            'slug' => Str::slug($metadata->title),
            'source_metadata_id' => $metadata->id,
        ]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        return $data;
    }
}
