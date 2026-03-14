<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoCuentaAnaliticaResource\Pages;
use App\Filament\Resources\VehiculoCuentaAnaliticaResource\RelationManagers;
use App\Models\VehiculoCuentaAnalitica;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoCuentaAnaliticaResource extends Resource
{
    protected static ?string $model = VehiculoCuentaAnalitica::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'Asig. Cuenta Analítica';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vehiculo_id')
                ->relationship('vehiculo', 'placas')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('cuenta_analitica_id')
                ->label('Cuenta Analítica')
                ->relationship('cuentaAnalitica', 'nombre')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\DatePicker::make('fecha_inicio')
                ->default(now())
                ->required(),

            Forms\Components\DatePicker::make('fecha_fin'),

            Forms\Components\Toggle::make('activo')
                ->default(true),

            Forms\Components\Hidden::make('asignado_por_user_id')
                ->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cuentaAnalitica.nombre')
                    ->label('Cuenta Analítica')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->date(),

                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
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
            'index' => Pages\ListVehiculoCuentaAnaliticas::route('/'),
            'create' => Pages\CreateVehiculoCuentaAnalitica::route('/create'),
            'edit' => Pages\EditVehiculoCuentaAnalitica::route('/{record}/edit'),
        ];
    }
}
