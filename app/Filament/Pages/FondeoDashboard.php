<?php

namespace App\Filament\Pages;

use App\Models\Fondeo;
use App\Models\Vehiculo;
use App\Services\TarjetaSaldoService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class FondeoDashboard extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.fondeo-dashboard';
    protected static ?string $navigationLabel = 'Fondeo Operativo';
    protected static ?string $title = 'Fondeo Dashboard';

    /* ==========================================================
       TABLA PRINCIPAL
    ==========================================================*/

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Vehiculo::query()->with([
                    'tarjetaActiva.tarjeta',
                    'departamentoActivo.departamento',
                    'localidadActiva.localidad',
                ])
            )
            ->defaultSort('id', 'asc')
            ->columns([

                Tables\Columns\TextColumn::make('tarjeta')
                    ->label('Tarjeta')
                    ->state(fn ($record) =>
                        $record->tarjetaActiva?->tarjeta?->numero ?? 'Sin tarjeta'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->tarjetaActiva?->tarjeta?->numero ? 'success' : 'gray'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_economico')
                    ->label('No. Económico')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('placas')
                    ->label('Placas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('marca')
                    ->label('Marca')
                    ->sortable(),

                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('localidadActiva.localidad.nombre')
                    ->label('Localidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('departamentoActivo.departamento.nombre')
                    ->label('Departamento')
                    ->sortable(),

                Tables\Columns\TextColumn::make('asignado')
                    ->label('Asignado (L)')
                    ->state(fn ($record) => $this->obtenerAsignado($record)),

                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio $/L')
                    ->state(fn ($record) =>
                        number_format($this->obtenerUltimoPrecioLitro($record), 2)
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('objetivo_pesos')
                    ->label('Objetivo sugerido $')
                    ->state(fn ($record) =>
                        number_format($this->obtenerObjetivoPesos($record), 2)
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('movimientos_one_card')
                    ->label('Movimientos netos One Card $')
                    ->state(fn ($record) =>
                        number_format($this->obtenerMovimientosOneCardPesos($record), 2)
                    )
                    ->badge()
                    ->color(fn ($record) => $this->obtenerMovimientosOneCardPesos($record) >= 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_financiero')
                    ->label('Saldo real $')
                    ->state(fn ($record) =>
                        number_format($this->obtenerSaldoFinancieroPesos($record), 2)
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $this->obtenerSaldoFinancieroPesos($record) > 0 ? 'success' : 'danger'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_operativo_litros')
                    ->label('Saldo Operativo (L)')
                    ->state(fn ($record) => $this->calcularSaldo($record))
                    ->badge()
                    ->color(fn ($record) => $this->colorSemaforo($record))
                    ->icon(fn ($record) => $this->iconoSemaforo($record))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reposicion_pesos')
                    ->label('Reposición sugerida $')
                    ->state(fn ($record) =>
                        number_format($this->obtenerReposicionPesos($record), 2)
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('impacto_one_card')
                    ->label('Impacto One Card (L)')
                    ->state(fn ($record) => number_format($this->obtenerImpactoOneCard($record), 2))
                    ->badge()
                    ->color(fn ($record) => $this->obtenerImpactoOneCard($record) >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('porcentaje')
                    ->label('% Fondo Disponible')
                    ->state(fn ($record) => $this->calcularPorcentaje($record) . '%')
                    ->badge()
                    ->color(fn ($record) => $this->colorSemaforo($record)),

                Tables\Columns\TextColumn::make('pendiente')
                    ->label('Pendiente Reposición (L)')
                    ->state(fn ($record) => $this->calcularPendiente($record)),
            ])

            ->actions([

            Tables\Actions\Action::make('fondear')
                ->label('Fondear')
                ->visible(fn ($record) =>
                    (auth()->user()?->can('fondeo.create') ?? false)
                    && $this->calcularPendiente($record) > 0
                    && $this->tieneConfigActiva($record)
                )
                ->authorize(fn () => auth()->user()?->can('fondeo.create') ?? false)
                    ->form([
                        TextInput::make('importe_fondeado')
                            ->label('Monto real a fondear')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn ($record) =>
                                round(
                                    $this->calcularPendiente($record)
                                    * $this->obtenerUltimoPrecioLitro($record),
                                    2
                                )
                            )
                            ->helperText('Este es el saldo real que se reflejará en la tarjeta. Puede ser mayor que el objetivo sugerido.'),

                        TextInput::make('litros_fondeados')
                            ->label('Litros estimados')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn ($record) =>
                                $this->calcularPendiente($record)
                            )
                            ->helperText('Valor sugerido por el sistema. Si el monto real es distinto, ajusta este valor para conservar la equivalencia.'),

                        Textarea::make('comentario')
                            ->label('Comentario'),
                    ])
                ->action(function ($record, $data) {

                    $pendiente = $this->calcularPendiente($record);

                    if ($data['litros_fondeados'] > $pendiente) {
                        Notification::make()
                            ->title('Error')
                            ->body('No puedes fondear más litros que el pendiente.')
                            ->danger()
                            ->send();
                        return;
                    }

                    Fondeo::create([
                        'vehiculo_id' => $record->id,
                        'litros_fondeados' => $data['litros_fondeados'],
                        'importe_fondeado' => $data['importe_fondeado'],
                        'fecha_fondeado' => now(),
                        'fondeado_por_user_id' => auth()->id(),
                        'comentario' => $data['comentario'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Fondeo registrado correctamente')
                        ->success()
                        ->send();
                }),
        ]);
}

    /* ==========================================================
       MÉTODOS AUXILIARES
    ==========================================================*/

    protected function obtenerAsignado($record)
    {
        return $this->saldoService()->obtenerAsignadoLitrosVehiculo($record);
    }

    protected function obtenerFondeadoTotal($record)
    {
        return $this->saldoService()->obtenerFondeadoLitrosVehiculo($record);
    }

    protected function obtenerConsumidoTotal($record)
    {
        return $this->saldoService()->obtenerConsumidoLitrosVehiculo($record);
    }

    protected function calcularSaldo($record)
    {
        return $this->saldoService()->obtenerSaldoDisponibleLitrosVehiculo($record);
    }

    protected function obtenerImpactoOneCard($record)
    {
        return $this->saldoService()->obtenerImpactoOneCardLitrosVehiculo($record);
    }

    protected function calcularPendiente($record)
    {
        return $this->saldoService()->obtenerPendienteLitrosVehiculo($record);
    }

    protected function calcularPorcentaje($record)
    {
        return $this->saldoService()->obtenerPorcentajeVehiculo($record);
    }

    protected function colorSemaforo($record)
    {
        return $this->saldoService()->obtenerColorSemaforoVehiculo($record);
    }

    protected function iconoSemaforo($record)
    {
        return $this->saldoService()->obtenerIconoSemaforoVehiculo($record);
    }

    protected function obtenerUltimoPrecioLitro($record)
    {
        return $this->saldoService()->obtenerUltimoPrecioLitroVehiculo($record);
    }

    protected function obtenerObjetivoPesos($record): float
    {
        return round(
            $this->obtenerAsignado($record)
            * $this->obtenerUltimoPrecioLitro($record),
            2
        );
    }

    protected function obtenerMovimientosOneCardPesos($record): float
    {
        $tarjeta = $record->tarjetaActiva?->tarjeta;

        if (! $tarjeta) {
            return 0.0;
        }

        return $this->saldoService()->obtenerMovimientosOneCardPesosTarjeta($tarjeta);
    }

    protected function obtenerSaldoFinancieroPesos($record): float
    {
        $tarjeta = $record->tarjetaActiva?->tarjeta;

        if (! $tarjeta) {
            return 0.0;
        }

        return $this->saldoService()->obtenerSaldoFinancieroPesosTarjeta($tarjeta);
    }

    protected function obtenerReposicionPesos($record): float
    {
        return round(
            $this->calcularPendiente($record)
            * $this->obtenerUltimoPrecioLitro($record),
            2
        );
    }

    protected function tieneConfigActiva($record)
    {
        return $this->obtenerAsignado($record) > 0;
    }

    protected function saldoService(): TarjetaSaldoService
    {
        return app(TarjetaSaldoService::class);
    }

    /* ==========================================================
       MÉTRICAS PARA WIDGET SUPERIOR
    ==========================================================*/

    public function getCriticosCount(): int
    {
        // Crítico = saldo <= 0 o porcentaje < 40
        return Vehiculo::query()
            ->get()
            ->filter(function ($vehiculo) {
                $asignado = $this->obtenerAsignado($vehiculo);
                if ($asignado <= 0) return false;

                $saldo = $this->calcularSaldo($vehiculo);
                if ($saldo <= 0) return true;

                return $this->calcularPorcentaje($vehiculo) < 40;
            })
            ->count();
    }

    public function getAtencionCount(): int
    {
        // Atención = 40% a 69% (y saldo > 0)
        return Vehiculo::query()
            ->get()
            ->filter(function ($vehiculo) {
                $asignado = $this->obtenerAsignado($vehiculo);
                if ($asignado <= 0) return false;

                $saldo = $this->calcularSaldo($vehiculo);
                if ($saldo <= 0) return false;

                $p = $this->calcularPorcentaje($vehiculo);
                return $p >= 40 && $p < 70;
            })
            ->count();
    }

    public function getSaludablesCount(): int
    {
        // Saludable = >= 70% (y saldo > 0)
        return Vehiculo::query()
            ->get()
            ->filter(function ($vehiculo) {
                $asignado = $this->obtenerAsignado($vehiculo);
                if ($asignado <= 0) return false;

                $saldo = $this->calcularSaldo($vehiculo);
                if ($saldo <= 0) return false;

                return $this->calcularPorcentaje($vehiculo) >= 70;
            })
            ->count();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pagina.fondeo-operativo.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
