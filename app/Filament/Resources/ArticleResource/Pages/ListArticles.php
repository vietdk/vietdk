<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected static ?string $navigationLabel = 'All Articles';
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationGroup = 'Articles';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canApproveArticles() ?? false;
    }
}
