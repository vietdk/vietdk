<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Import/Export';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Import History';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('filename')
                    ->disabled(),

                Forms\Components\TextInput::make('file_type')
                    ->disabled(),

                Forms\Components\TextInput::make('status')
                    ->disabled(),

                Forms\Components\TextInput::make('articles_created')
                    ->disabled(),

                Forms\Components\Textarea::make('error_log')
                    ->disabled()
                    ->visible(fn ($record) => $record?->isFailed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Uploaded By')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('file_type')
                    ->colors([
                        'info' => Import::TYPE_DOCX,
                        'success' => Import::TYPE_XLSX,
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => Import::STATUS_PENDING,
                        'warning' => Import::STATUS_PROCESSING,
                        'success' => Import::STATUS_COMPLETED,
                        'danger' => Import::STATUS_FAILED,
                    ]),

                Tables\Columns\TextColumn::make('articles_created')
                    ->label('Articles'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Uploaded At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Import::getStatuses()),

                Tables\Filters\SelectFilter::make('file_type')
                    ->options(Import::getFileTypes()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Import $record) {
                        $record->update(['status' => Import::STATUS_PENDING]);
                        \App\Jobs\ProcessImport::dispatch($record);
                    })
                    ->visible(fn (Import $record) => $record->isFailed()),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListImports::route('/'),
        ];
    }
}
