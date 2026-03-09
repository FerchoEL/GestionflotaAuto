<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocalidadResource\Pages;
use App\Filament\Resources\LocalidadResource\RelationManagers;
use App\Models\Localidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class LocalidadResource extends Resource
{
    protected static ?string $model = Localidad::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Localidades';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(150),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalidads::route('/'),
            'create' => Pages\CreateLocalidad::route('/create'),
            'edit' => Pages\EditLocalidad::route('/{record}/edit'),
        ];
    }
}
