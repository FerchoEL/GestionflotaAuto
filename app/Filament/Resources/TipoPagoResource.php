<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoPagoResource\Pages;
use App\Models\TipoPago;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TipoPagoResource extends Resource
{
    protected static ?string $model = TipoPago::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Tipos de pago';
    protected static ?string $modelLabel = 'Tipo de pago';
    protected static ?string $pluralModelLabel = 'Tipos de pago';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->label('Nombre del tipo de pago'),

            TextInput::make('periodicidad_dias')
                ->required()
                ->numeric()
                ->minValue(1)
                ->label('Periodicidad (días)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),

                TextColumn::make('periodicidad_dias')
                    ->label('Periodicidad (días)')
                    ->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoPagos::route('/'),
            'create' => Pages\CreateTipoPago::route('/create'),
            'edit' => Pages\EditTipoPago::route('/{record}/edit'),
        ];
    }
}
