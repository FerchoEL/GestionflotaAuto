<?php

namespace App\Filament\Pages;

use App\Models\CargaCombustible;
use App\Models\CuentaAnalitica;
use App\Models\Vehiculo;
use App\Services\RendimientoService;
use App\Services\TarjetaMovimientoService;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistrarCargaExtemporanea extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Carga extemporánea';
    protected static ?string $title = 'Registrar Carga Extemporánea';
    protected static string $view = 'filament.pages.registrar-carga-extemporanea';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'fecha_carga' => now()->format('Y-m-d H:i:s'),
            'es_extemporanea' => true,
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'responsable']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('vehiculo_id')
                    ->label('Vehículo')
                    ->options(function () {
                        $user = Auth::user();

                        $query = Vehiculo::query()
                            ->whereHas('responsables', fn (Builder $query) => $query->where('activo', true))
                            ->whereHas('tarjetas', fn (Builder $query) => $query->where('activo', true));

                        if ($user?->hasRole('admin')) {
                            return $query
                                ->orderBy('numero_economico')
                                ->orderBy('placas')
                                ->get()
                                ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->display_name]);
                        }

                        return $query
                            ->whereHas('responsables', function (Builder $query) use ($user) {
                                $query->where('responsable_user_id', $user?->id)
                                    ->where('activo', true);
                            })
                            ->orderBy('numero_economico')
                            ->orderBy('placas')
                            ->get()
                            ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->display_name]);
                    })
                    ->searchable()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if (! $state) {
                            return;
                        }

                        $vehiculo = Vehiculo::query()
                            ->with(['cuentaAnaliticaActiva'])
                            ->find($state);

                        if ($vehiculo?->cuentaAnaliticaActiva?->cuenta_analitica_id) {
                            $set('cuenta_analitica_id', $vehiculo->cuentaAnaliticaActiva->cuenta_analitica_id);
                        }
                    }),

                DateTimePicker::make('fecha_carga')
                    ->label('Fecha de carga')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y h:i A')
                    ->format('Y-m-d H:i:s'),

                TextInput::make('km_odometro')
                    ->label('Kilometraje (odómetro)')
                    ->numeric()
                    ->required()
                    ->rule('gt:0')
                    ->helperText('En carga extemporánea puedes registrar un kilometraje anterior al último capturado si corresponde a una fecha pasada.'),

                TextInput::make('litros')
                    ->label('Litros exactos del ticket')
                    ->numeric()
                    ->required()
                    ->extraInputAttributes([
                        'step' => '0.001',
                        'inputmode' => 'decimal',
                    ])
                    ->rule('gt:0')
                    ->helperText('Captura los litros exactos que aparecen en el ticket. Puedes registrar hasta 3 decimales.'),

                TextInput::make('precio_litro')
                    ->label('Precio por litro del ticket')
                    ->numeric()
                    ->required()
                    ->rule('gt:0')
                    ->helperText('Precio mostrado en el ticket. Se conserva solo como referencia/auditoría.'),

                TextInput::make('importe')
                    ->label('Importe total del ticket')
                    ->numeric()
                    ->required()
                    ->rule('gt:0')
                    ->helperText('Captura el total real pagado en el ticket. Este será el monto oficial para movimientos y reportes.'),

                Select::make('cuenta_analitica_id')
                    ->label('Cuenta Analítica')
                    ->options(fn () => CuentaAnalitica::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Checkbox::make('es_extemporanea')
                    ->label('Marcar como carga extemporánea')
                    ->default(true)
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('motivo_correccion')
                    ->label('Motivo de corrección')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Describe por qué esta carga se registra fuera del flujo normal.'),

                $this->mobileImageUpload('foto_odometro_path', 'Foto odómetro', 'cargas/odometro'),

                $this->mobileImageUpload('foto_ticket_path', 'Foto ticket', 'cargas/ticket'),

                $this->mobileImageUpload('foto_bomba_path', 'Foto bomba', 'cargas/bomba'),
            ])
            ->columns(2)
            ->statePath('data');
    }

    private function mobileImageUpload(string $statePath, string $label, string $directory): FileUpload
    {
        return FileUpload::make($statePath)
            ->label($label)
            ->image()
            ->required()
            ->acceptedFileTypes(['image/*'])
            ->disk('public')
            ->directory($directory)
            ->maxSize(20480)
            ->extraAlpineAttributes([
                'x-init' => <<<'JS'
const captureFixTimer = setInterval(() => {
    if (! pond) {
        return;
    }

    pond.setOptions({ allowSyncAcceptAttribute: false });

    if ($refs.input) {
        $refs.input.removeAttribute('accept');
    }

    clearInterval(captureFixTimer);
}, 50);
JS,
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data['user_id'] = Auth::id();
        $data['es_extemporanea'] = true;
        $data['registrada_por_user_id'] = Auth::id();
        $data['fecha_registro_real'] = now();

        $vehiculo = Vehiculo::find($data['vehiculo_id']);

        if (! $vehiculo) {
            Notification::make()
                ->title('Vehículo no válido')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if (! $vehiculo->responsables()->where('activo', true)->exists()) {
            Notification::make()
                ->title('No se puede registrar la carga')
                ->body('El vehículo no tiene un responsable activo asignado.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $tarjetaCombustibleId = app(TarjetaMovimientoService::class)
            ->resolverTarjetaIdVehiculoEnFecha($data['vehiculo_id'] ?? null, $data['fecha_carga'] ?? null);

        if (! $tarjetaCombustibleId) {
            Notification::make()
                ->title('No se puede registrar la carga')
                ->body('El vehículo no tiene una tarjeta asignada para la fecha de la carga.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $data['tarjeta_combustible_id'] = $tarjetaCombustibleId;

        try {
            $carga = CargaCombustible::create($data);

            app(RendimientoService::class)->recalcularDesdeCarga($carga);

            Notification::make()
                ->title('La carga extemporánea se registró correctamente')
                ->body('Esta acción recalculó rendimientos y alertas posteriores.')
                ->success()
                ->send();

            $this->form->fill([
                'vehiculo_id' => $data['vehiculo_id'],
                'fecha_carga' => now()->format('Y-m-d H:i:s'),
                'cuenta_analitica_id' => $data['cuenta_analitica_id'] ?? null,
                'es_extemporanea' => true,
            ]);
        } catch (Throwable $e) {
            Log::error('Error al registrar carga extemporánea', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            Notification::make()
                ->title('No se pudo registrar la carga extemporánea')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
