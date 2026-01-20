<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->record->submitForReview())
                ->visible(fn () => $this->record->canBeSubmitted()),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isDraft() || auth()->user()->isAdmin()),
        ];
    }
}
