<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListPendingReviewArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;
    protected static ?string $title = 'Pending Review Articles';
    protected static ?string $navigationLabel = 'Pending Review';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Articles';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['author', 'updatedBy'])
            ->pendingReview()
            ->forUser(auth()->user())
            ->latest('updated_at');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('approve')
                ->label('Approve Selected')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    $records->each(fn ($article) => $article->approve());

                    Notification::make()
                        ->success()
                        ->title('Articles approved')
                        ->body($records->count() . ' articles have been approved.')
                        ->send();
                })
                ->visible(fn () => auth()->user()?->canApproveArticles() ?? false),

            Tables\Actions\BulkAction::make('reject')
                ->label('Reject Selected')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    $records->each(fn ($article) => $article->reject());

                    Notification::make()
                        ->success()
                        ->title('Articles rejected')
                        ->body($records->count() . ' articles have been rejected.')
                        ->send();
                })
                ->visible(fn () => auth()->user()?->canApproveArticles() ?? false),
        ];
    }
}
