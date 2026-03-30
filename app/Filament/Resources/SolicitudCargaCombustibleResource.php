<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudCargaCombustibleResource\Pages;
use App\Filament\Resources\SolicitudCargaCombustibleResource\RelationManagers;
use App\Models\SolicitudCargaCombustible;
use App\Models\CargaCombustible;
use App\Models\CuentaAnalitica;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolicitudCargaCombustibleResource extends Resource
{
    protected static ?string $model = CargaCombustible::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Solicitudes de Combustible';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'responsable']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'responsable']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'vehiculo',
                'usuario',
                'cuentaAnalitica',
                'vehiculo.cuentaAnaliticaActiva.cuentaAnalitica',
            ]);

        $user = auth()->user();

        if ($user->hasRole('responsable')) {
            return $query->whereHas('vehiculo.responsableActivo', function ($q) use ($user) {
                $q->where('responsable_user_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('vehiculo')
                ->label('Vehículo')
                ->content(fn ($record) => $record?->vehiculo?->display_name ?? '—'),

            Forms\Components\Placeholder::make('fecha_carga')
                ->label('Fecha de carga')
                ->content(fn ($record) => $record?->fecha_carga ?? '—'),

            Forms\Components\Placeholder::make('km_odometro')
                ->label('KM odómetro')
                ->content(fn ($record) => $record?->km_odometro ?? '—'),

            Forms\Components\Placeholder::make('litros')
                ->label('Litros')
                ->content(fn ($record) => $record?->litros ?? '—'),

            Forms\Components\Placeholder::make('precio_litro')
                ->label('Precio/L')
                ->content(fn ($record) => $record?->precio_litro ?? '—'),

            Forms\Components\Placeholder::make('importe')
                ->label('Importe')
                ->content(fn ($record) => $record?->importe ?? '—'),

            Forms\Components\Select::make('cuenta_analitica_id')
                ->label('Cuenta Analítica')
                ->options(
                    CuentaAnalitica::query()
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Puedes asignar o corregir la cuenta analítica de esta solicitud.'),

            Forms\Components\Placeholder::make('sugerida')
                ->label('Cuenta analítica sugerida')
                ->content(function ($record) {
                    return $record?->vehiculo?->cuentaAnaliticaActiva?->cuentaAnalitica?->nombre ?? 'Sin sugerencia';
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_carga', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_carga')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Placas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Capturado por')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('km_odometro')
                    ->label('KM')
                    ->sortable(),

                Tables\Columns\TextColumn::make('litros')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_litro')
                    ->label('Precio/L')
                    ->sortable(),

                Tables\Columns\TextColumn::make('importe')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cuentaAnalitica.nombre')
                    ->label('Cuenta Analítica')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('vehiculo.cuentaAnaliticaActiva.cuentaAnalitica.nombre')
                    ->label('Sugerida')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('usarSugerida')
                    ->label('Usar sugerida')
                    ->icon('heroicon-o-light-bulb')
                    ->visible(fn ($record) => filled($record?->vehiculo?->cuentaAnaliticaActiva?->cuenta_analitica_id))
                    ->action(function ($record) {
                        $sugerida = $record?->vehiculo?->cuentaAnaliticaActiva?->cuenta_analitica_id;

                        if ($sugerida) {
                            $record->update([
                                'cuenta_analitica_id' => $sugerida,
                            ]);
                        }
                    }),

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
            'index' => Pages\ListSolicitudCargaCombustibles::route('/'),
            'create' => Pages\CreateSolicitudCargaCombustible::route('/create'),
            'edit' => Pages\EditSolicitudCargaCombustible::route('/{record}/edit'),
        ];
    }
}
