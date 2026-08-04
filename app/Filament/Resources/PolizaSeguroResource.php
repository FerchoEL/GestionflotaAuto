<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolizaSeguroResource\Pages;
use App\Models\Aseguradora;
use App\Models\PolizaSeguro;
use App\Models\TipoPago;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PolizaSeguroResource extends Resource
{
    protected static ?string $model = PolizaSeguro::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documentación';
    protected static ?string $navigationLabel = 'Pólizas de seguro';
    protected static ?string $modelLabel = 'Póliza de seguro';
    protected static ?string $pluralModelLabel = 'Pólizas de seguro';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('poliza-seguro.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('poliza-seguro.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('poliza-seguro.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('poliza-seguro.update') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('vehiculo_documento_id')
                ->relationship('vehiculoDocumento', 'nombre')
                ->required()
                ->searchable()
                ->preload()
                ->label('Documento de vehículo'),

            Select::make('aseguradora_id')
                ->options(fn () => Aseguradora::where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->label('Aseguradora'),

            TextInput::make('costo_poliza')
                ->numeric()
                ->prefix('$')
                ->step('0.01')
                ->required()
                ->label('Costo de la póliza'),

            Select::make('tipo_pago_id')
                ->options(fn () => TipoPago::orderBy('nombre')
                    ->pluck('nombre', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->label('Tipo de pago'),

            Textarea::make('notas')
                ->maxLength(500)
                ->rows(3)
                ->label('Notas'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehiculoDocumento.vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vehiculoDocumento.nombre')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('aseguradora.nombre')
                    ->label('Aseguradora')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('costo_poliza')
                    ->label('Costo')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('tipoPago.nombre')
                    ->label('Tipo de pago')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('aseguradora_id')
                    ->options(fn () => Aseguradora::where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id'))
                    ->label('Aseguradora'),

                Tables\Filters\SelectFilter::make('tipo_pago_id')
                    ->options(fn () => TipoPago::orderBy('nombre')
                        ->pluck('nombre', 'id'))
                    ->label('Tipo de pago'),
            ])
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
            'index' => Pages\ListPolizasSeguro::route('/'),
            'create' => Pages\CreatePolizaSeguro::route('/create'),
            'edit' => Pages\EditPolizaSeguro::route('/{record}/edit'),
        ];
    }
}
