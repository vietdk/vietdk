<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExportTemplateResource\Pages;
use App\Models\ExportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ExportTemplateResource extends Resource
{
    protected static ?string $model = ExportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Import/Export';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Export Templates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('file_path')
                    ->label('Template File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->required()
                    ->disk('local')
                    ->directory('templates')
                    ->helperText('Upload a DOCX template file'),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_default')
                    ->label('Set as Default Template'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),

                Tables\Columns\TextColumn::make('bulletin_exports_count')
                    ->counts('bulletinExports')
                    ->label('Uses'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('set_default')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (ExportTemplate $record) {
                        $record->setAsDefault();
                        Notification::make()
                            ->title('Template set as default')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ExportTemplate $record) => !$record->is_default),

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
            'index' => Pages\ListExportTemplates::route('/'),
            'create' => Pages\CreateExportTemplate::route('/create'),
            'edit' => Pages\EditExportTemplate::route('/{record}/edit'),
        ];
    }
}
