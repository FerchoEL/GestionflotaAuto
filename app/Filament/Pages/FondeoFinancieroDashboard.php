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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
                TarjetaCombustible::query()
                    ->with([
                        'vehiculoActivo.vehiculo.tarjetaActiva.tarjeta',
                        'vehiculoActivo.vehiculo.fondeoConfigActual',
                        'vehiculoActivo.vehiculo.departamentoActivo.departamento',
                        'vehiculoActivo.vehiculo.localidadActiva.localidad',
                    ])
                    ->leftJoin('vehiculo_tarjetas as vt', function ($join): void {
                        $join->on('vt.tarjeta_combustible_id', '=', 'tarjeta_combustibles.id')
                            ->where('vt.activo', true);
                    })
                    ->leftJoin('vehiculos as v', 'v.id', '=', 'vt.vehiculo_id')
                    ->leftJoin('vehiculo_fondeo_configs as vfc', function ($join): void {
                        $join->on('vfc.vehiculo_id', '=', 'v.id')
                            ->where('vfc.activo', true);
                    })
                    ->leftJoinSub(
                        DB::table('tarjeta_saldo_movimientos')
                            ->selectRaw('tarjeta_combustible_id, SUM(monto) as movimientos_one_card')
                            ->groupBy('tarjeta_combustible_id'),
                        'movimientos_totales',
                        fn ($join) => $join->on('movimientos_totales.tarjeta_combustible_id', '=', 'tarjeta_combustibles.id')
                    )
                    ->select('tarjeta_combustibles.*')
                    ->selectRaw("
                        CASE WHEN vt.vehiculo_id IS NULL THEN 0 ELSE 1 END as tiene_vehiculo_asignado,
                        COALESCE(NULLIF(TRIM(v.numero_economico), ''), NULLIF(TRIM(v.placas), ''), 'Sin vehiculo') as vehiculo,
                        COALESCE(vfc.litros_asignados, 0) as asignado_litros,
                        COALESCE((
                            SELECT cc.precio_litro
                            FROM carga_combustibles cc
                            WHERE cc.vehiculo_id = v.id
                              AND cc.precio_litro IS NOT NULL
                              AND cc.precio_litro > 0
                            ORDER BY cc.fecha_carga DESC, cc.id DESC
                            LIMIT 1
                        ), (
                            SELECT ROUND(f.importe_fondeado / f.litros_fondeados, 2)
                            FROM fondeos f
                            WHERE f.vehiculo_id = v.id
                              AND f.litros_fondeados > 0
                              AND f.importe_fondeado > 0
                            ORDER BY f.fecha_fondeado DESC, f.id DESC
                            LIMIT 1
                        ), 0) as precio_litro,
                        ROUND(
                            COALESCE(vfc.litros_asignados, 0) * COALESCE((
                                SELECT cc.precio_litro
                                FROM carga_combustibles cc
                                WHERE cc.vehiculo_id = v.id
                                  AND cc.precio_litro IS NOT NULL
                                  AND cc.precio_litro > 0
                                ORDER BY cc.fecha_carga DESC, cc.id DESC
                                LIMIT 1
                            ), (
                                SELECT ROUND(f.importe_fondeado / f.litros_fondeados, 2)
                                FROM fondeos f
                                WHERE f.vehiculo_id = v.id
                                  AND f.litros_fondeados > 0
                                  AND f.importe_fondeado > 0
                                ORDER BY f.fecha_fondeado DESC, f.id DESC
                                LIMIT 1
                            ), 0),
                            2
                        ) as objetivo_pesos,
                        ROUND(COALESCE(movimientos_totales.movimientos_one_card, 0), 2) as ajustes_one_card,
                        ROUND(COALESCE(movimientos_totales.movimientos_one_card, 0), 2) as saldo_financiero,
                        ROUND(
                            CASE
                                WHEN vt.vehiculo_id IS NULL THEN 0
                                WHEN COALESCE((
                                    SELECT cc.precio_litro
                                    FROM carga_combustibles cc
                                    WHERE cc.vehiculo_id = v.id
                                      AND cc.precio_litro IS NOT NULL
                                      AND cc.precio_litro > 0
                                    ORDER BY cc.fecha_carga DESC, cc.id DESC
                                    LIMIT 1
                                ), (
                                    SELECT ROUND(f.importe_fondeado / f.litros_fondeados, 2)
                                    FROM fondeos f
                                    WHERE f.vehiculo_id = v.id
                                      AND f.litros_fondeados > 0
                                      AND f.importe_fondeado > 0
                                    ORDER BY f.fecha_fondeado DESC, f.id DESC
                                    LIMIT 1
                                ), 0) > 0
                                    THEN COALESCE(movimientos_totales.movimientos_one_card, 0) / COALESCE((
                                        SELECT cc.precio_litro
                                        FROM carga_combustibles cc
                                        WHERE cc.vehiculo_id = v.id
                                          AND cc.precio_litro IS NOT NULL
                                          AND cc.precio_litro > 0
                                        ORDER BY cc.fecha_carga DESC, cc.id DESC
                                        LIMIT 1
                                    ), (
                                        SELECT ROUND(f.importe_fondeado / f.litros_fondeados, 2)
                                        FROM fondeos f
                                        WHERE f.vehiculo_id = v.id
                                          AND f.litros_fondeados > 0
                                          AND f.importe_fondeado > 0
                                        ORDER BY f.fecha_fondeado DESC, f.id DESC
                                        LIMIT 1
                                    ), 0)
                                ELSE 0
                            END,
                            2
                        ) as saldo_operativo_litros,
                        ROUND(
                            CASE
                                WHEN vt.vehiculo_id IS NULL THEN 0
                                ELSE GREATEST(
                                    (
                                        COALESCE(vfc.litros_asignados, 0) * COALESCE((
                                            SELECT cc.precio_litro
                                            FROM carga_combustibles cc
                                            WHERE cc.vehiculo_id = v.id
                                              AND cc.precio_litro IS NOT NULL
                                              AND cc.precio_litro > 0
                                            ORDER BY cc.fecha_carga DESC, cc.id DESC
                                            LIMIT 1
                                        ), (
                                            SELECT ROUND(f.importe_fondeado / f.litros_fondeados, 2)
                                            FROM fondeos f
                                            WHERE f.vehiculo_id = v.id
                                              AND f.litros_fondeados > 0
                                              AND f.importe_fondeado > 0
                                            ORDER BY f.fecha_fondeado DESC, f.id DESC
                                            LIMIT 1
                                        ), 0)
                                    ) - COALESCE(movimientos_totales.movimientos_one_card, 0),
                                    0
                                )
                            END,
                            2
                        ) as pendiente_pesos
                    ")
            )
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderByDesc('tiene_vehiculo_asignado')
                    ->orderBy('numero');
            })
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Tarjeta')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.placas')
                    ->label('Placa')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.marca')
                    ->label('Marca')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.modelo')
                    ->label('Modelo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.localidadActiva.localidad.nombre')
                    ->label('Localidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehiculoActivo.vehiculo.departamentoActivo.departamento.nombre')
                    ->label('Departamento')
                    ->sortable(),

                Tables\Columns\TextColumn::make('asignado_litros')
                    ->label('Asignado (L)')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_litro')
                    ->label('Precio $/L')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('objetivo_pesos')
                    ->label('Objetivo $')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('ajustes_one_card')
                    ->label('Movimientos One Card $')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->badge()
                    ->color(fn (TarjetaCombustible $record): string => ((float) $record->ajustes_one_card) >= 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_financiero')
                    ->label('Saldo Financiero $')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->badge()
                    ->color(function (TarjetaCombustible $record): string {
                        return $this->obtenerColorSemaforoTarjeta($record);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_operativo_litros')
                    ->label('Saldo Operativo (L)')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('pendiente_pesos')
                    ->label('Reposicion $')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asignacion')
                    ->label('Asignacion')
                    ->placeholder('Todas las tarjetas')
                    ->options([
                        'con_vehiculo' => 'Solo con vehiculo asignado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) !== 'con_vehiculo') {
                            return $query;
                        }

                        return $query->whereNotNull('vt.vehiculo_id');
                    }),
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
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportar_solicitud_recarga')
                    ->label('Exportar Solicitud de Recarga')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportSolicitudRecarga())
                    ->color('success'),
            ]);
    }

    public function exportSolicitudRecarga()
    {
        $templatePath = storage_path('app/templates/one-card-template.xlsx');

        if (! file_exists($templatePath)) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró la plantilla de exportación.')
                ->danger()
                ->send();

            return null;
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName('SOLICITUD_DE_RECARGAS');

        $tarjetas = TarjetaCombustible::query()
            ->with(['vehiculoActivo.vehiculo'])
            ->whereHas('vehiculoActivo')
            ->get();

        $row = 4;
        foreach ($tarjetas as $tarjeta) {
            $importe = $this->calcularImporteDeCarga($tarjeta);

            $sheet->setCellValue('B' . $row, $tarjeta->empleado_one_card);
            $sheet->setCellValue('E' . $row, $importe);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'solicitud_recarga.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'solicitud_recarga_');

        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    protected function calcularImporteDeCarga(TarjetaCombustible $tarjeta): float
    {
        $saldoFinanciero = $this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($tarjeta);
        $objetivo = $this->saldoService()->obtenerFondoObjetivoPesosTarjeta($tarjeta);

        return max(round($objetivo - $saldoFinanciero, 2), 0);
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

    protected function obtenerColorSemaforoTarjeta(TarjetaCombustible $tarjeta): string
    {
        if (! $tarjeta->tiene_vehiculo_asignado) {
            return 'gray';
        }

        $saldo = (float) $tarjeta->saldo_operativo_litros;
        $asignado = (float) $tarjeta->asignado_litros;

        if ($saldo <= 0) {
            return 'danger';
        }

        if ($asignado <= 0) {
            return 'gray';
        }

        $porcentaje = (int) round(($saldo / $asignado) * 100);

        if ($porcentaje < 40) {
            return 'danger';
        }

        if ($porcentaje < 70) {
            return 'warning';
        }

        return 'success';
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
