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
            Vehiculo::query()->with(['tarjetaActiva.tarjeta'])
        )
        ->defaultSort('id', 'asc')
        ->columns([

            // ✅ NUEVA COLUMNA TARJETA
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

            // Vehículo
            Tables\Columns\TextColumn::make('numero_economico')
                ->label('No. Económico')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('placas')
                ->label('Placas')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('asignado')
                ->label('Asignado (L)')
                ->state(fn ($record) => $this->obtenerAsignado($record)),

            Tables\Columns\TextColumn::make('saldo_actual')
                ->label('Saldo Operativo (L)')
                ->state(fn ($record) => $this->calcularSaldo($record))
                ->badge()
                ->color(fn ($record) => $this->colorSemaforo($record))
                ->icon(fn ($record) => $this->iconoSemaforo($record)),

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

            Tables\Columns\TextColumn::make('precio')
                ->label('Precio $/L')
                ->state(fn ($record) =>
                    number_format($this->obtenerUltimoPrecioLitro($record), 2)
                ),

            Tables\Columns\TextColumn::make('estimado')
                ->label('$ Estimado Reposición')
                ->state(fn ($record) =>
                    number_format(
                        $this->calcularPendiente($record)
                        * $this->obtenerUltimoPrecioLitro($record),
                        2
                    )
                ),
        ])

        ->actions([

            Tables\Actions\Action::make('fondear')
                ->label('Fondear')
                ->visible(fn ($record) =>
                    $this->calcularPendiente($record) > 0
                    && $this->tieneConfigActiva($record)
                )
                ->form([
                    TextInput::make('litros_fondeados')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn ($record) =>
                            $this->calcularPendiente($record)
                        )
                        ->default(fn ($record) =>
                            $this->calcularPendiente($record)
                        ),

                    TextInput::make('importe_fondeado')
                        ->numeric()
                        ->required()
                        ->default(fn ($record) =>
                            round(
                                $this->calcularPendiente($record)
                                * $this->obtenerUltimoPrecioLitro($record),
                                2
                            )
                        ),

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
        return auth()->user()->hasAnyRole([
            'admin',
            'fondeo'
        ]);
    }
}
