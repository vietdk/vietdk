<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CrawledMetadataResource\Pages;
use App\Models\Article;
use App\Models\CrawledMetadata;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CrawledMetadataResource extends Resource
{
    protected static ?string $model = CrawledMetadata::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Crawler';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Crawled Items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('news_source_id')
                    ->relationship('newsSource', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('status')
                    ->options(CrawledMetadata::getStatuses())
                    ->required(),

                Forms\Components\DateTimePicker::make('published_date')
                    ->label('Published Date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('newsSource.name')
                    ->label('Source')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => CrawledMetadata::STATUS_NEW,
                        'success' => CrawledMetadata::STATUS_USED,
                        'gray' => CrawledMetadata::STATUS_SKIPPED,
                    ]),

                Tables\Columns\TextColumn::make('published_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Crawled At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CrawledMetadata::getStatuses()),

                Tables\Filters\SelectFilter::make('news_source_id')
                    ->relationship('newsSource', 'name')
                    ->label('Source'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_url')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->url, true)
                    ->color('gray'),

                Tables\Actions\Action::make('create_draft')
                    ->label('Create Draft')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('author_id')
                            ->label('Assign To')
                            ->options(User::query()->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (CrawledMetadata $record, array $data) {
                        if (!$record->isNew()) {
                            return;
                        }

                        $article = Article::create([
                            'title' => $record->title,
                            'author_id' => $data['author_id'],
                            'source_metadata_id' => $record->id,
                            'status' => Article::STATUS_DRAFT,
                        ]);

                        $record->markAsUsed();

                        Notification::make()
                            ->title('Draft created')
                            ->body("Draft assigned to {$article->author->name}.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isNew()),

                Tables\Actions\Action::make('skip')
                    ->label('Skip')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->markAsSkipped())
                    ->visible(fn ($record) => $record->isNew()),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_drafts')
                        ->label('Assign Drafts')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('author_id')
                                ->label('Assign To')
                                ->options(User::query()->pluck('name', 'id'))
                                ->default(fn () => auth()->id())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $assignedCount = 0;

                            foreach ($records as $record) {
                                if (!$record->isNew()) {
                                    continue;
                                }

                                Article::create([
                                    'title' => $record->title,
                                    'author_id' => $data['author_id'],
                                    'source_metadata_id' => $record->id,
                                    'status' => Article::STATUS_DRAFT,
                                ]);

                                $record->markAsUsed();
                                $assignedCount++;
                            }

                            Notification::make()
                                ->title('Drafts created')
                                ->body("Created {$assignedCount} draft(s).")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('mark_skipped')
                        ->label('Mark as Skipped')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->markAsSkipped())
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCrawledMetadata::route('/'),
        ];
    }
}
