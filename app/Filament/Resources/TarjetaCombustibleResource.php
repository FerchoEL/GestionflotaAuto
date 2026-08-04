<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TarjetaCombustibleResource\Pages;
use App\Models\TarjetaCombustible;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TarjetaCombustibleResource extends Resource
{
    protected static ?string $model = TarjetaCombustible::class;
    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Tarjetas de combustible';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero')
                    ->required()
                    ->dehydrateStateUsing(fn (?string $state): string => TarjetaCombustible::normalizarNumero($state))
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('descripcion'),

                Forms\Components\TextInput::make('empleado_one_card')
                    ->label('Empleado One Card')
                    ->maxLength(50),

                Forms\Components\TextInput::make('convenio_id_one_card')
                    ->label('Convenio ID One Card')
                    ->maxLength(50),

                Forms\Components\TextInput::make('convenio_one_card')
                    ->label('Convenio One Card')
                    ->maxLength(255),

                Forms\Components\TextInput::make('sucursal_one_card')
                    ->label('Sucursal One Card')
                    ->maxLength(255),

                Forms\Components\TextInput::make('nombre_one_card')
                    ->label('Nombre One Card')
                    ->maxLength(255),

                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }
    public static function canAccess(): bool
    {
        return auth()->user()?->can('tarjeta-combustible.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('tarjeta-combustible.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('tarjeta-combustible.update') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('tarjeta-combustible.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('tarjeta-combustible.delete') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descripcion'),

                Tables\Columns\TextColumn::make('nombre_one_card')
                    ->label('Nombre One Card')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('empleado_one_card')
                    ->label('Empleado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('convenio_id_one_card')
                    ->label('Convenio ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sucursal_one_card')
                    ->label('Sucursal')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
