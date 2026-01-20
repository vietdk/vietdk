<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->record->submitForReview())
                ->visible(fn () => $this->record->canBeSubmitted()),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->approve())
                ->visible(fn () => $this->record->canBeApproved() && auth()->user()->canApproveArticles()),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->reject())
                ->visible(fn () => $this->record->canBeRejected() && auth()->user()->canApproveArticles()),

            Actions\Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->publish())
                ->visible(fn () => $this->record->canBePublished() && auth()->user()->canPublishArticles()),
        ];
    }
}
