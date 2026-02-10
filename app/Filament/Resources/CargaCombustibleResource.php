<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CargaCombustibleResource\Pages;
use App\Filament\Resources\CargaCombustibleResource\RelationManagers;
use App\Models\CargaCombustible;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CargaCombustibleResource extends Resource
{
    protected static ?string $model = CargaCombustible::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vehiculo_id')
                ->label('Vehículo')
                ->options(function () {
                    $user = Auth::user();

                    if ($user->hasAnyRole(['admin', 'activos', 'administracion'])) {
                        return Vehiculo::query()->orderBy('placas')->pluck('placas', 'id');
                    }

                    $idsChofer = VehiculoChofer::where('chofer_user_id', $user->id)
                        ->where('activo', true)
                        ->pluck('vehiculo_id');

                    $idsResp = VehiculoResponsable::where('responsable_user_id', $user->id)
                        ->where('activo', true)
                        ->pluck('vehiculo_id');

                    $ids = $idsChofer->merge($idsResp)->unique()->values();

                    return Vehiculo::whereIn('id', $ids)->orderBy('placas')->pluck('placas', 'id');
                })
                ->searchable()
                ->required(),

            Forms\Components\DateTimePicker::make('fecha_carga')
                ->label('Fecha de carga')
                ->default(now())
                ->required(),

            Forms\Components\TextInput::make('km_odometro')
                ->label('Kilometraje (odómetro)')
                ->numeric()
                ->required()
                ->rules([
                    fn ($get) => function ($attribute, $value, $fail) use ($get) {
                        $vehiculoId = $get('vehiculo_id');
                        if (! $vehiculoId) return;

                        $ultima = CargaCombustible::where('vehiculo_id', $vehiculoId)
                            ->orderByDesc('id')
                            ->first();

                        if ($ultima && (int)$value <= (int)$ultima->km_odometro) {
                            $fail('El kilometraje debe ser mayor al último registrado.');
                        }
                    }
                ]),

            Forms\Components\TextInput::make('litros')
                ->numeric()
                ->required()
                ->rule('gt:0'),

            Forms\Components\TextInput::make('importe')
                ->numeric()
                ->label('Importe (opcional)')
                ->nullable(),

            Forms\Components\FileUpload::make('foto_odometro_path')
                ->label('Foto odómetro')
                ->image()
                ->required()
                ->directory('cargas/odometro'),

            Forms\Components\FileUpload::make('foto_ticket_path')
                ->label('Foto ticket')
                ->image()
                ->required()
                ->directory('cargas/ticket'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')->label('Vehículo')->searchable(),
            Tables\Columns\TextColumn::make('fecha_carga')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('km_odometro')->sortable(),
            Tables\Columns\TextColumn::make('litros')->sortable(),
            Tables\Columns\TextColumn::make('importe')->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('usuario.name')->label('Capturado por')->toggleable(isToggledHiddenByDefault: true),
             ])
            ->defaultSort('fecha_carga', 'desc')
            ->actions([
            Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCargaCombustibles::route('/'),
            'create' => Pages\CreateCargaCombustible::route('/create'),
            'edit' => Pages\EditCargaCombustible::route('/{record}/edit'),
        ];
    }
}
