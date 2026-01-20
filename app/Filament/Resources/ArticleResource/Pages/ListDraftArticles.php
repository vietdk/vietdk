<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDraftArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;
    protected static ?string $title = 'Draft Articles';
    protected static ?string $navigationLabel = 'Drafts';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Articles';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['author', 'updatedBy'])
            ->drafts()
            ->forUser(auth()->user())
            ->latest('updated_at');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }
}
