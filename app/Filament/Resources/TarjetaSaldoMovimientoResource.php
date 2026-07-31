<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TarjetaSaldoMovimientoResource\Pages;
use App\Models\TarjetaCombustible;
use App\Models\TarjetaSaldoMovimiento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TarjetaSaldoMovimientoResource extends Resource
{
    protected static ?string $model = TarjetaSaldoMovimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Movimientos One Card';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'tarjeta',
                'tarjetaDestino',
                'cuentaConcentradora',
                'registradoPor',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('fecha_movimiento')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'retiro_one_card' => 'Retiro',
                        'traspaso_salida_one_card' => 'Transferencia salida',
                        'traspaso_entrada_one_card' => 'Transferencia entrada',
                        'ajuste_one_card' => 'Ajuste',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'retiro_one_card' => 'warning',
                        'traspaso_salida_one_card' => 'danger',
                        'traspaso_entrada_one_card' => 'success',
                        'ajuste_one_card' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tarjeta.numero')
                    ->label('Tarjeta origen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tarjetaDestino.numero')
                    ->label('Tarjeta destino')
                    ->placeholder('N/A')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cuentaConcentradora.nombre')
                    ->label('Cuenta concentradora')
                    ->placeholder('N/A')
                    ->searchable(),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('MXN', true)
                    ->sortable()
                    ->color(fn (string $state): string => ((float) $state) >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registradoPor.name')
                    ->label('Registrado por')
                    ->placeholder('N/A')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('comentario')
                    ->label('Comentario')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_movimiento', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'retiro_one_card' => 'Retiro',
                        'traspaso_salida_one_card' => 'Transferencia salida',
                        'traspaso_entrada_one_card' => 'Transferencia entrada',
                        'ajuste_one_card' => 'Ajuste',
                    ]),

                Tables\Filters\SelectFilter::make('tarjeta_combustible_id')
                    ->label('Tarjeta origen')
                    ->options(fn (): array => TarjetaCombustible::query()
                        ->orderBy('numero')
                        ->pluck('numero', 'id')
                        ->all())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('tarjeta_destino_id')
                    ->label('Tarjeta destino')
                    ->options(fn (): array => TarjetaCombustible::query()
                        ->orderBy('numero')
                        ->pluck('numero', 'id')
                        ->all())
                    ->searchable(),

                Tables\Filters\Filter::make('solo_transferencias')
                    ->label('Solo transferencias')
                    ->query(fn (Builder $query): Builder => $query->whereIn('tipo', [
                        'traspaso_salida_one_card',
                        'traspaso_entrada_one_card',
                    ])),

                Tables\Filters\Filter::make('rango_fecha')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Desde'),
                        DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_movimiento', '>=', $date)
                            )
                            ->when(
                                $data['hasta'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_movimiento', '<=', $date)
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTarjetaSaldoMovimientos::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo',
            'activos',
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
