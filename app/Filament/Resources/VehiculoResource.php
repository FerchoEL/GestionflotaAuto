<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Filament\Resources\VehiculoResource\RelationManagers;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use Illuminate\Validation\Rule;


class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Flota';
    protected static ?string $navigationLabel = 'Vehículos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipo_vehiculo_id')
                ->relationship('tipoVehiculo', 'nombre')
                ->required(),

                Forms\Components\Select::make('departamento_id')
                    ->relationship('departamento', 'nombre'),

                Forms\Components\Select::make('localidad_id')
                    ->relationship('localidad', 'nombre'),

                Forms\Components\Select::make('estatus_id')
                    ->relationship('estatus', 'nombre')
                    ->required(),

                Forms\Components\Select::make('tipo_combustible')
                    ->label('Tipo de combustible')
                    ->options([
                        'gasolina' => 'Gasolina',
                        'diesel' => 'Diésel',
                    ])
                    ->nullable(),

                Forms\Components\Select::make('transmision')
                    ->label('Transmisión')
                    ->options([
                        'manual' => 'Manual',
                        'automatica' => 'Automática',
                    ])
                    ->nullable(),
                Forms\Components\Select::make('centro_costo_id')
                    ->label('Centro de costo')
                    ->relationship('centroCosto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\TextInput::make('placas')
                    ->required()
                    ->maxLength(8)
                    ->live(onBlur: true) // 🔥 valida al salir del campo
                    ->unique(
                        table: Vehiculo::class,
                        column: 'placas',
                        ignoreRecord: true
                    )
                     ->validationMessages([
                        'unique' => 'Esta placa ya está registrada.',
                    ]),
                Forms\Components\TextInput::make('vin')
                    ->required()
                    ->maxLength(17)
                    ->live(onBlur: true)
                    ->unique(
                        table: Vehiculo::class,
                        column: 'vin',
                        ignoreRecord: true
                    ) 
                    ->validationMessages([
                        'unique' => 'Este VIN ya está registrado.',
                    ]),

                Forms\Components\TextInput::make('marca')->required(),
                Forms\Components\TextInput::make('modelo')->required(),
                Forms\Components\TextInput::make('anio')->numeric(),
                Forms\Components\TextInput::make('color'),

                Forms\Components\TextInput::make('capacidad_tanque_litros')->numeric(),
                Forms\Components\TextInput::make('rendimiento_optimo_km_l')->numeric()->required(),
                Forms\Components\TextInput::make('tolerancia_pct')->numeric(),
                Forms\Components\Select::make('responsable_user_id')
                ->label('Responsable del vehículo')
                ->options(User::role('responsable')->pluck('name', 'id'))
                ->required(),

            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('placas')->searchable(),
                Tables\Columns\TextColumn::make('marca'),
                Tables\Columns\TextColumn::make('modelo'),
                Tables\Columns\TextColumn::make('tipoVehiculo.nombre')->label('Tipo'),
                Tables\Columns\TextColumn::make('estatus.nombre')->label('Estatus'),
                
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
            'index' => Pages\ListVehiculos::route('/'),
            'create' => Pages\CreateVehiculo::route('/create'),
            'edit' => Pages\EditVehiculo::route('/{record}/edit'),
        ];
    }
}
