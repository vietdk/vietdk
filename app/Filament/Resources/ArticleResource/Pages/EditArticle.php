<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Article Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('original_title')
                            ->label('Vietnamese/Original Title')
                            ->maxLength(255)
                            ->helperText('Original title in Vietnamese or source language'),

                        Forms\Components\TextInput::make('original_url')
                            ->label('Original Article URL')
                            ->url()
                            ->maxLength(500)
                            ->helperText('URL of the original source article'),

                        Forms\Components\Textarea::make('body')
                            ->required()
                            ->rows(20)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('excerpt')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('Brief summary of the article. Auto-generated if left empty.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Organization')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('tone_id')
                            ->relationship('tone', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Select::make('campaign_id')
                            ->relationship('campaign', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(Article::getStatuses())
                            ->required()
                            ->default(Article::STATUS_DRAFT)
                            ->disabled(fn () => !auth()->user()->isEditor()),

                        Forms\Components\Hidden::make('author_id')
                            ->default(fn () => auth()->id()),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published Date')
                            ->disabled(fn (?Article $record) => $record?->status !== Article::STATUS_PUBLISHED),
                    ])
                    ->columns(2)
                    ->visible(fn () => auth()->user()->isEditor()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $this->record->refresh();
                    $this->record->approve();

                    \Filament\Notifications\Notification::make()
                        ->title('Article approved')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.articles.approved');
                })
                ->visible(fn () => $this->record->canBeApproved() && auth()->user()->canApproveArticles()),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject();

                    \Filament\Notifications\Notification::make()
                        ->title('Article rejected')
                        ->warning()
                        ->send();

                    return redirect()->route('filament.admin.resources.articles.drafts');
                })
                ->visible(fn () => $this->record->canBeRejected() && auth()->user()->canApproveArticles()),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isDraft() || auth()->user()->isAdmin()),
        ];
    }

    protected function getFormActions(): array
    {
        // For pending review articles, show Save Changes button
        if ($this->record->isPendingReview()) {
            return [
                Actions\Action::make('save')
                    ->label('Save Changes')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function () {
                        $this->save(shouldRedirect: false);

                        \Filament\Notifications\Notification::make()
                            ->title('Changes saved')
                            ->success()
                            ->send();
                    }),
            ];
        }

        // For draft or rejected articles, show Submit for Review button
        return [
            Actions\Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $this->record->refresh();
                    $this->record->submitForReview();

                    \Filament\Notifications\Notification::make()
                        ->title('Article submitted for review')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.articles.pending-review');
                })
                ->visible(fn () => $this->record->canBeSubmitted()),
        ];
    }
}
