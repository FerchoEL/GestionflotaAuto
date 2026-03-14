<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResponsableResource\Pages;
use App\Filament\Resources\VehiculoResponsableResource\RelationManagers;
use App\Models\VehiculoResponsable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class VehiculoResponsableResource extends Resource
{
    protected static ?string $model = VehiculoResponsable::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Asig.Responsables a Vehículo';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
                ->relationship('vehiculo', 'placas')
                ->searchable()
                ->required(),

                Forms\Components\Select::make('responsable_user_id')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('fecha_inicio')
                    ->required(),

                Forms\Components\DatePicker::make('fecha_fin'),

                Forms\Components\Toggle::make('activo')
                    ->default(true),    
            ]);
    }

    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')->label('Vehículo'),
                Tables\Columns\TextColumn::make('responsable.name')->label('Responsable'),
                Tables\Columns\TextColumn::make('fecha_inicio')->date(),
                Tables\Columns\TextColumn::make('fecha_fin')->date(),
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
            'index' => Pages\ListVehiculoResponsables::route('/'),
            'create' => Pages\CreateVehiculoResponsable::route('/create'),
            'edit' => Pages\EditVehiculoResponsable::route('/{record}/edit'),
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
}
