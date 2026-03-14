<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FondeoResource\Pages;
use App\Models\Fondeo;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model; 

class FondeoResource extends Resource
{
    protected static ?string $model = Fondeo::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Fondeo manual';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('vehiculo_id')
                ->relationship('vehiculo', 'placas')
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('litros_fondeados')
                ->numeric()
                ->required()
                ->minValue(0),

            Forms\Components\TextInput::make('importe_fondeado')
                ->numeric()
                ->required()
                ->minValue(0),

            Forms\Components\DateTimePicker::make('fecha_fondeado')
                ->default(now())
                ->required(),

            Forms\Components\Textarea::make('comentario')
                ->maxLength(255),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('litros_fondeados')
                    ->label('Litros Fondeados'),

                Tables\Columns\TextColumn::make('importe_fondeado')
                    ->money('MXN', true),

                Tables\Columns\TextColumn::make('fecha_fondeado')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('fondeadoPor.name')
                    ->label('Registrado por'),

            ])
            ->defaultSort('fecha_fondeado', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFondeos::route('/'),
            'create' => Pages\CreateFondeo::route('/create'),
            'edit' => Pages\EditFondeo::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo'
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo'
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo'
        ]);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
