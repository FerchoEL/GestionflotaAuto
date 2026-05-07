<?php

namespace App\Filament\Pages;

use App\Models\CuentaConcentradora;
use App\Models\Fondeo;
use App\Models\TarjetaCombustible;
use App\Models\TarjetaSaldoMovimiento;
use App\Services\TarjetaSaldoService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class FondeoFinancieroDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Fondeo Financiero';

    protected static ?string $title = 'Fondeo Financiero';

    protected static string $view = 'filament.pages.fondeo-financiero-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TarjetaCombustible::query()->with([
                    'vehiculoActivo.vehiculo.tarjetaActiva.tarjeta',
                    'vehiculoActivo.vehiculo.fondeoConfigActual',
                ])
            )
            ->defaultSort('numero')
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Tarjeta')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('vehiculo')
                    ->label('Vehiculo')
                    ->state(function (TarjetaCombustible $record): string {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        return $vehiculo?->display_name ?: 'Sin vehiculo';
                    }),

                Tables\Columns\TextColumn::make('asignado_litros')
                    ->label('Asignado (L)')
                    ->state(function (TarjetaCombustible $record): string {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        return number_format($vehiculo ? $this->saldoService()->obtenerAsignadoLitrosVehiculo($vehiculo) : 0, 2);
                    }),

                Tables\Columns\TextColumn::make('precio_litro')
                    ->label('Precio $/L')
                    ->state(function (TarjetaCombustible $record): string {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        return number_format($vehiculo ? $this->saldoService()->obtenerUltimoPrecioLitroVehiculo($vehiculo) : 0, 2);
                    }),

                Tables\Columns\TextColumn::make('objetivo_pesos')
                    ->label('Objetivo $')
                    ->state(fn (TarjetaCombustible $record): string => number_format($this->saldoService()->obtenerFondoObjetivoPesosTarjeta($record), 2)),

                Tables\Columns\TextColumn::make('saldo_base_pesos')
                    ->label('Base Operativa $')
                    ->state(fn (TarjetaCombustible $record): string => number_format($this->saldoService()->obtenerSaldoBasePesosTarjeta($record), 2)),

                Tables\Columns\TextColumn::make('ajustes_one_card')
                    ->label('Movimientos One Card $')
                    ->state(fn (TarjetaCombustible $record): string => number_format($this->saldoService()->obtenerMovimientosOneCardPesosTarjeta($record), 2))
                    ->badge()
                    ->color(fn (TarjetaCombustible $record): string => $this->saldoService()->obtenerMovimientosOneCardPesosTarjeta($record) >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('saldo_financiero')
                    ->label('Saldo Financiero $')
                    ->state(fn (TarjetaCombustible $record): string => number_format($this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($record), 2))
                    ->badge()
                    ->color(function (TarjetaCombustible $record): string {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        return $vehiculo ? $this->saldoService()->obtenerColorSemaforoVehiculo($vehiculo) : 'gray';
                    }),

                Tables\Columns\TextColumn::make('saldo_operativo_litros')
                    ->label('Saldo Operativo (L)')
                    ->state(function (TarjetaCombustible $record): string {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        return number_format($vehiculo ? $this->saldoService()->obtenerSaldoDisponibleLitrosVehiculo($vehiculo) : 0, 2);
                    }),

                Tables\Columns\TextColumn::make('pendiente_pesos')
                    ->label('Reposicion $')
                    ->state(fn (TarjetaCombustible $record): string => number_format($this->saldoService()->obtenerMontoReposicionPesosTarjeta($record), 2)),
            ])
            ->actions([
                Tables\Actions\Action::make('fondear')
                    ->label('Fondear')
                    ->visible(fn (TarjetaCombustible $record): bool => $this->puedeFondearTarjeta($record))
                    ->form([
                        TextInput::make('litros_fondeados')
                            ->label('Litros a fondear')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn (TarjetaCombustible $record): float => $this->obtenerPendienteLitrosTarjeta($record)),
                        TextInput::make('importe_fondeado')
                            ->label('Importe a fondear')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn (TarjetaCombustible $record): float => $this->saldoService()->obtenerMontoReposicionPesosTarjeta($record)),
                        Textarea::make('comentario')
                            ->label('Comentario'),
                    ])
                    ->action(function (TarjetaCombustible $record, array $data): void {
                        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($record);

                        if (! $vehiculo) {
                            Notification::make()->title('Error')->body('La tarjeta no tiene un vehiculo activo.')->danger()->send();
                            return;
                        }

                        Fondeo::create([
                            'vehiculo_id' => $vehiculo->id,
                            'litros_fondeados' => $data['litros_fondeados'],
                            'importe_fondeado' => $data['importe_fondeado'],
                            'fecha_fondeado' => now(),
                            'fondeado_por_user_id' => auth()->id(),
                            'comentario' => $data['comentario'] ?? null,
                        ]);

                        Notification::make()->title('Fondeo registrado correctamente')->success()->send();
                    }),

                Tables\Actions\Action::make('retirar')
                    ->label('Retirar')
                    ->visible(fn (TarjetaCombustible $record): bool => $this->puedeRetirarTarjeta($record))
                    ->form($this->buildRetiroForm())
                    ->action(function (TarjetaCombustible $record, array $data): void {
                        $monto = abs((float) $data['monto']);
                        $saldoActual = $this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($record);

                        if ($monto > $saldoActual) {
                            Notification::make()
                                ->title('Error')
                                ->body('No puedes retirar mas del saldo financiero disponible. Saldo detectado: $' . number_format($saldoActual, 2))
                                ->danger()
                                ->send();
                            return;
                        }

                        TarjetaSaldoMovimiento::create([
                            'tarjeta_combustible_id' => $record->id,
                            'tipo' => 'retiro_one_card',
                            'monto' => -$monto,
                            'fecha_movimiento' => $data['fecha_movimiento'],
                            'cuenta_concentradora_id' => $data['cuenta_concentradora_id'],
                            'registrado_por_user_id' => auth()->id(),
                            'referencia' => $data['referencia'] ?? null,
                            'comentario' => $data['comentario'] ?? null,
                        ]);

                        Notification::make()->title('Retiro a concentradora registrado correctamente')->success()->send();
                    }),

                Tables\Actions\Action::make('transferir')
                    ->label('Transferir')
                    ->visible(fn (TarjetaCombustible $record): bool => $this->puedeTransferirTarjeta($record))
                    ->form([
                        Select::make('tarjeta_destino_id')
                            ->label('Tarjeta destino')
                            ->options(fn (TarjetaCombustible $record): array => TarjetaCombustible::query()
                                ->whereKeyNot($record->id)
                                ->where('activo', true)
                                ->orderBy('numero')
                                ->pluck('numero', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        ...$this->buildMovimientoForm(),
                    ])
                    ->action(function (TarjetaCombustible $record, array $data): void {
                        $monto = abs((float) $data['monto']);
                        $saldoActual = $this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($record);

                        if ($monto > $saldoActual) {
                            Notification::make()
                                ->title('Error')
                                ->body('No puedes transferir mas del saldo financiero disponible. Saldo detectado: $' . number_format($saldoActual, 2))
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $data, $monto): void {
                            TarjetaSaldoMovimiento::create([
                                'tarjeta_combustible_id' => $record->id,
                                'tipo' => 'traspaso_salida_one_card',
                                'monto' => -$monto,
                                'fecha_movimiento' => $data['fecha_movimiento'],
                                'tarjeta_destino_id' => $data['tarjeta_destino_id'],
                                'registrado_por_user_id' => auth()->id(),
                                'referencia' => $data['referencia'] ?? null,
                                'comentario' => $data['comentario'] ?? null,
                            ]);

                            TarjetaSaldoMovimiento::create([
                                'tarjeta_combustible_id' => $data['tarjeta_destino_id'],
                                'tipo' => 'traspaso_entrada_one_card',
                                'monto' => $monto,
                                'fecha_movimiento' => $data['fecha_movimiento'],
                                'tarjeta_destino_id' => $record->id,
                                'registrado_por_user_id' => auth()->id(),
                                'referencia' => $data['referencia'] ?? null,
                                'comentario' => $data['comentario'] ?? null,
                            ]);
                        });

                        Notification::make()->title('Transferencia registrada correctamente')->success()->send();
                    }),

                Tables\Actions\Action::make('ajustar')
                    ->label('Ajustar')
                    ->form([
                        TextInput::make('monto')
                            ->label('Monto de ajuste')
                            ->numeric()
                            ->required(),
                        DateTimePicker::make('fecha_movimiento')
                            ->label('Fecha')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        TextInput::make('referencia')
                            ->label('Referencia'),
                        Textarea::make('comentario')
                            ->label('Comentario'),
                    ])
                    ->action(function (TarjetaCombustible $record, array $data): void {
                        TarjetaSaldoMovimiento::create([
                            'tarjeta_combustible_id' => $record->id,
                            'tipo' => 'ajuste_one_card',
                            'monto' => (float) $data['monto'],
                            'fecha_movimiento' => $data['fecha_movimiento'],
                            'registrado_por_user_id' => auth()->id(),
                            'referencia' => $data['referencia'] ?? null,
                            'comentario' => $data['comentario'] ?? null,
                        ]);

                        Notification::make()->title('Ajuste registrado correctamente')->success()->send();
                    }),
            ]);
    }

    protected function buildMovimientoForm(): array
    {
        return [
            TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->required()
                ->minValue(0.01),
            DateTimePicker::make('fecha_movimiento')
                ->label('Fecha')
                ->default(now())
                ->seconds(false)
                ->required(),
            TextInput::make('referencia')
                ->label('Referencia'),
            Textarea::make('comentario')
                ->label('Comentario'),
        ];
    }

    protected function buildRetiroForm(): array
    {
        return [
            Select::make('cuenta_concentradora_id')
                ->label('Cuenta concentradora destino')
                ->options(fn (): array => CuentaConcentradora::query()
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            ...$this->buildMovimientoForm(),
        ];
    }

    protected function puedeRetirarTarjeta(TarjetaCombustible $tarjeta): bool
    {
        return $this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($tarjeta) > 0
            && CuentaConcentradora::query()->where('activo', true)->exists();
    }

    protected function puedeFondearTarjeta(TarjetaCombustible $tarjeta): bool
    {
        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($tarjeta);

        if (! $vehiculo) {
            return false;
        }

        return $this->saldoService()->obtenerPendienteLitrosVehiculo($vehiculo) > 0
            && $this->saldoService()->obtenerUltimoPrecioLitroVehiculo($vehiculo) > 0;
    }

    protected function puedeTransferirTarjeta(TarjetaCombustible $tarjeta): bool
    {
        return TarjetaCombustible::query()
            ->whereKeyNot($tarjeta->id)
            ->where('activo', true)
            ->exists();
    }

    protected function obtenerPendienteLitrosTarjeta(TarjetaCombustible $tarjeta): float
    {
        $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($tarjeta);

        if (! $vehiculo) {
            return 0.0;
        }

        return $this->saldoService()->obtenerPendienteLitrosVehiculo($vehiculo);
    }

    protected function saldoService(): TarjetaSaldoService
    {
        return app(TarjetaSaldoService::class);
    }

    public function getCriticasCount(): int
    {
        return TarjetaCombustible::query()
            ->with('vehiculoActivo.vehiculo')
            ->get()
            ->filter(function (TarjetaCombustible $tarjeta): bool {
                $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($tarjeta);

                if (! $vehiculo || $this->saldoService()->obtenerAsignadoLitrosVehiculo($vehiculo) <= 0) {
                    return false;
                }

                $saldo = $this->saldoService()->obtenerSaldoDisponibleLitrosVehiculo($vehiculo);

                if ($saldo <= 0) {
                    return true;
                }

                return $this->saldoService()->obtenerPorcentajeVehiculo($vehiculo) < 40;
            })
            ->count();
    }

    public function getAtencionCount(): int
    {
        return TarjetaCombustible::query()
            ->with('vehiculoActivo.vehiculo')
            ->get()
            ->filter(function (TarjetaCombustible $tarjeta): bool {
                $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($tarjeta);

                if (! $vehiculo || $this->saldoService()->obtenerAsignadoLitrosVehiculo($vehiculo) <= 0) {
                    return false;
                }

                $saldo = $this->saldoService()->obtenerSaldoDisponibleLitrosVehiculo($vehiculo);
                $porcentaje = $this->saldoService()->obtenerPorcentajeVehiculo($vehiculo);

                return $saldo > 0 && $porcentaje >= 40 && $porcentaje < 70;
            })
            ->count();
    }

    public function getSaludablesCount(): int
    {
        return TarjetaCombustible::query()
            ->with('vehiculoActivo.vehiculo')
            ->get()
            ->filter(function (TarjetaCombustible $tarjeta): bool {
                $vehiculo = $this->saldoService()->obtenerVehiculoActivoTarjeta($tarjeta);

                if (! $vehiculo || $this->saldoService()->obtenerAsignadoLitrosVehiculo($vehiculo) <= 0) {
                    return false;
                }

                return $this->saldoService()->obtenerSaldoDisponibleLitrosVehiculo($vehiculo) > 0
                    && $this->saldoService()->obtenerPorcentajeVehiculo($vehiculo) >= 70;
            })
            ->count();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo',
        ]);
    }
}
