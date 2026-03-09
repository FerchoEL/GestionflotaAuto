<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoTarjetaResource\Pages;
use App\Filament\Resources\VehiculoTarjetaResource\RelationManagers;
use App\Models\VehiculoTarjeta;
use App\Models\Vehiculo;
use App\Models\TarjetaCombustible;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoTarjetaResource extends Resource
{
    protected static ?string $model = VehiculoTarjeta::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Asignar Tarjetas de Combustible';
    protected static ?string $navigationGroup = 'Activos';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
                ->label('Vehículo')
                ->options(Vehiculo::query()->orderBy('placas')->pluck('placas', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('tarjeta_combustible_id')
                ->label('Tarjeta')
                ->options(
                    TarjetaCombustible::query()
                        ->where('activo', true)
                        ->orderBy('numero')
                        ->pluck('numero', 'id')
                )
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Solo tarjetas activas. Si reasignas, se cierra el histórico anterior.'),

            Forms\Components\DatePicker::make('fecha_inicio')
                ->label('Fecha inicio')
                ->default(now())
                ->required(),

            Forms\Components\DatePicker::make('fecha_fin')
                ->label('Fecha fin')
                ->disabled()
                ->dehydrated(),

            Forms\Components\Toggle::make('activo')
                ->default(true)
                ->disabled() // siempre lo controlamos por lógica (histórico)
                ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tarjeta.numero')
                ->label('Tarjeta')
                ->searchable()
                ->sortable(),

                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Placas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('activo')
                    ->boolean()
                    ->sortable(),                           
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListVehiculoTarjetas::route('/'),
            'create' => Pages\CreateVehiculoTarjeta::route('/create'),
            'edit' => Pages\EditVehiculoTarjeta::route('/{record}/edit'),
        ];
    }
}
