<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApprovedArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;
    protected static ?string $title = 'Approved Articles';
    protected static ?string $navigationLabel = 'Approved';
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Articles';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['author', 'approvedBy'])
            ->approved()
            ->forUser(auth()->user())
            ->latest('approved_at');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }
}
