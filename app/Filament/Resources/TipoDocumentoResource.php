<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoDocumentoResource\Pages;
use App\Models\TipoDocumento;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TipoDocumentoResource extends Resource
{
    protected static ?string $model = TipoDocumento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documentación';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Tipos de documento';
    protected static ?string $modelLabel = 'Tipo de documento';
    protected static ?string $pluralModelLabel = 'Tipos de documento';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->label('Nombre'),

            Toggle::make('requiere_vigencia')
                ->label('Requiere vigencia')
                ->live(),

            TextInput::make('dias_alerta_previa')
                ->numeric()
                ->minValue(1)
                ->default(15)
                ->required()
                ->visible(fn ($get): bool => (bool) $get('requiere_vigencia'))
                ->label('Días de alerta previa'),

            Toggle::make('es_obligatorio')
                ->label('Es obligatorio'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('requiere_vigencia')
                    ->label('Vigencia')
                    ->boolean(),

                TextColumn::make('dias_alerta_previa')
                    ->label('Alerta previa')
                    ->suffix(' días'),

                IconColumn::make('es_obligatorio')
                    ->label('Obligatorio')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoDocumentos::route('/'),
            'create' => Pages\CreateTipoDocumento::route('/create'),
            'edit' => Pages\EditTipoDocumento::route('/{record}/edit'),
        ];
    }
}
