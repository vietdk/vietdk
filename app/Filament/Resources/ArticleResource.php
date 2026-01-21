<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\CrawledMetadata;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public static function form(Form $form): Form
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

                        Forms\Components\RichEditor::make('body')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'orderedList',
                                'bulletList',
                                'h2',
                                'h3',
                                'blockquote',
                                'redo',
                                'undo',
                            ]),

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
                            ->required()
                            ->searchable()
                            ->preload(),

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

                        Forms\Components\Select::make('source_metadata_id')
                            ->relationship('sourceMetadata', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('Source Reference')
                            ->helperText('Link to crawled news source'),

                        Forms\Components\Placeholder::make('source_url')
                            ->label('Source URL')
                            ->content(function (Get $get) {
                                $metadataId = $get('source_metadata_id');

                                if (!$metadataId) {
                                    return '-';
                                }

                                $metadata = CrawledMetadata::find($metadataId);

                                return $metadata?->url ?? '-';
                            })
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => (bool) $get('source_metadata_id')),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('author.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tone.name')
                    ->label('Tone')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campaign')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => Article::STATUS_DRAFT,
                        'warning' => Article::STATUS_PENDING_REVIEW,
                        'info' => Article::STATUS_APPROVED,
                        'success' => Article::STATUS_PUBLISHED,
                        'danger' => Article::STATUS_REJECTED,
                    ]),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Last Editor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn ($record) => $record->updated_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn ($record) => $record->approved_at?->format('M d, Y'))
                    ->visible(fn () => auth()->user()?->canApproveArticles() ?? false),

                Tables\Columns\TextColumn::make('rejectedBy.name')
                    ->label('Rejected By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn ($record) => $record->rejected_at?->format('M d, Y'))
                    ->visible(fn () => auth()->user()?->canApproveArticles() ?? false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Article::getStatuses()),

                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),

                Tables\Filters\SelectFilter::make('author_id')
                    ->relationship('author', 'name')
                    ->label('Author')
                    ->visible(fn () => auth()->user()->isEditor()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('submit')
                        ->label('Submit for Review')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn (Article $record) => $record->submitForReview())
                        ->visible(fn (Article $record) => $record->canBeSubmitted()),

                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Article $record) => $record->approve())
                        ->visible(fn (Article $record) => $record->canBeApproved() && auth()->user()->canApproveArticles()),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Article $record) => $record->reject())
                        ->visible(fn (Article $record) => $record->canBeRejected() && auth()->user()->canApproveArticles()),

                    Tables\Actions\Action::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-globe-alt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Article $record) => $record->publish())
                        ->visible(fn (Article $record) => $record->canBePublished() && auth()->user()->canPublishArticles()),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (Article $record) => $record->isDraft() || auth()->user()->isAdmin()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'drafts' => Pages\ListDraftArticles::route('/drafts'),
            'pending-review' => Pages\ListPendingReviewArticles::route('/pending-review'),
            'approved' => Pages\ListApprovedArticles::route('/approved'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && !auth()->user()->isEditor()) {
            $query->where('author_id', auth()->id());
        }

        return $query;
    }
}
