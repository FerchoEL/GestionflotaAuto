<?php

namespace App\Filament\Pages;

use App\Models\Vehiculo;
use App\Models\CargaCombustible;
use App\Models\Fondeo;
use App\Models\VehiculoFondeoConfig;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model; 

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
            Tables\Columns\TextColumn::make('placas')
                ->label('Vehículo')
                ->searchable(),

            Tables\Columns\TextColumn::make('asignado')
                ->label('Asignado (L)')
                ->state(fn ($record) => $this->obtenerAsignado($record)),

            /* Tables\Columns\TextColumn::make('fondeado_total')
                ->label('Fondeado Total (L)')
                ->state(fn ($record) => $this->obtenerFondeadoTotal($record)),

            Tables\Columns\TextColumn::make('consumido_total')
                ->label('Consumido Total (L)')
                ->state(fn ($record) => $this->obtenerConsumidoTotal($record)), */

            Tables\Columns\TextColumn::make('saldo_actual')
                ->label('Saldo Operativo (L)')
                ->state(fn ($record) => $this->calcularSaldo($record))
                ->badge()
                ->color(fn ($record) => $this->colorSemaforo($record))
                ->icon(fn ($record) => $this->iconoSemaforo($record)),

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
                        ->minValue(1)
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
        return optional(
            VehiculoFondeoConfig::where('vehiculo_id', $record->id)
                ->where('activo', true)
                ->first()
        )->litros_asignados ?? 0;
    }

    protected function obtenerFondeadoTotal($record)
    {
        return Fondeo::where('vehiculo_id', $record->id)
            ->sum('litros_fondeados');
    }

    protected function obtenerConsumidoTotal($record)
    {
        return CargaCombustible::where('vehiculo_id', $record->id)
            ->sum('litros');
    }

    protected function calcularSaldo($record)
    {
        return $this->obtenerFondeadoTotal($record)
            - $this->obtenerConsumidoTotal($record);
    }

    protected function calcularPendiente($record)
    {
        $asignado = $this->obtenerAsignado($record);
        $saldo = $this->calcularSaldo($record);

        return max($asignado - $saldo, 0);
    }

    protected function calcularPorcentaje($record)
    {
        $asignado = $this->obtenerAsignado($record);
        $saldo = $this->calcularSaldo($record);

        if ($asignado <= 0) {
            return 0;
        }

        return round(($saldo / $asignado) * 100, 0);
    }

    protected function colorSemaforo($record)
    {
        $porcentaje = $this->calcularPorcentaje($record);
        $saldo = $this->calcularSaldo($record);

        if ($saldo <= 0) return 'danger';
        if ($porcentaje < 40) return 'danger';
        if ($porcentaje < 70) return 'warning';
        return 'success';
    }

    protected function iconoSemaforo($record)
    {
        $porcentaje = $this->calcularPorcentaje($record);
        $saldo = $this->calcularSaldo($record);

        if ($saldo <= 0) return 'heroicon-o-exclamation-triangle';
        if ($porcentaje < 40) return 'heroicon-o-exclamation-circle';
        if ($porcentaje < 70) return 'heroicon-o-exclamation';
        return 'heroicon-o-check-circle';
    }

    protected function obtenerUltimoPrecioLitro($record)
    {
        return CargaCombustible::where('vehiculo_id', $record->id)
            ->whereNotNull('precio_litro')
            ->orderByDesc('fecha_carga')
            ->value('precio_litro') ?? 0;
    }

    protected function tieneConfigActiva($record)
    {
        return VehiculoFondeoConfig::where('vehiculo_id', $record->id)
            ->where('activo', true)
            ->exists();
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