<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsSourceResource\Pages;
use App\Models\NewsSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsSourceResource extends Resource
{
    protected static ?string $model = NewsSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-rss';

    protected static ?string $navigationGroup = 'Crawler';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Source Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('base_url')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->label('Base URL'),

                        Forms\Components\TextInput::make('feed_url')
                            ->url()
                            ->maxLength(255)
                            ->label('RSS Feed URL')
                            ->helperText('Optional. Used only if you re-enable RSS crawling.'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('CSS Selectors (Optional)')
                    ->schema([
                        Forms\Components\KeyValue::make('selectors')
                            ->keyLabel('Element')
                            ->valueLabel('CSS Selector')
                            ->helperText('Used for HTML scraping fallback. Keys: title, date, link')
                            ->default([
                                'title' => 'h1, .article-title',
                                'date' => 'time, .date',
                                'link' => 'a.article-link',
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_url')
                    ->label('URL')
                    ->limit(40)
                    ->url(fn ($record) => $record->base_url, true),

                Tables\Columns\TextColumn::make('crawled_metadata_count')
                    ->counts('crawledMetadata')
                    ->label('Items'),

                Tables\Columns\TextColumn::make('last_crawled_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Crawled'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsSources::route('/'),
            'create' => Pages\CreateNewsSource::route('/create'),
            'edit' => Pages\EditNewsSource::route('/{record}/edit'),
        ];
    }
}
