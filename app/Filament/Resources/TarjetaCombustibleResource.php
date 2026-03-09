<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TarjetaCombustibleResource\Pages;
use App\Filament\Resources\TarjetaCombustibleResource\RelationManagers;
use App\Models\TarjetaCombustible;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TarjetaCombustibleResource extends Resource
{
    protected static ?string $model = TarjetaCombustible::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('descripcion'),

                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
            ->searchable()
            ->sortable(),

        Tables\Columns\TextColumn::make('descripcion'),

        Tables\Columns\BooleanColumn::make('activo'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTarjetaCombustibles::route('/'),
            'create' => Pages\CreateTarjetaCombustible::route('/create'),
            'edit' => Pages\EditTarjetaCombustible::route('/{record}/edit'),
        ];
    }
}
