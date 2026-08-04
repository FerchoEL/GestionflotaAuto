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
use App\Models\Vehiculo;
use App\Models\VehiculoChofer;
use App\Models\VehiculoResponsable;
use App\Support\FlotaScope;
use Illuminate\Database\Eloquent\Model;


class CargaCombustibleResource extends Resource
{
    protected static ?string $model = CargaCombustible::class;

    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Carga de combustible';

    public static function esChoferEstricto(): bool
    {
        $user = Auth::user();

        return $user?->hasRole('chofer')
            && ! $user?->hasAnyRole(['admin', 'responsable', 'activos']);
    }

    public static function form(Form $form): Form
    {
        $esChoferEstricto = static::esChoferEstricto();

        $mobileImageUpload = static function (
            string $statePath,
            string $label,
            string $directory,
            mixed $required,
            bool $forzarCamara = false
        ): Forms\Components\FileUpload {
            $component = Forms\Components\FileUpload::make($statePath)
                ->label($label)
                ->image()
                ->required($required)
                ->acceptedFileTypes(['image/*'])
                ->disk('public')
                ->directory($directory)
                ->maxSize(20480);

            if (! $forzarCamara) {
                return $component;
            }

            return $component
                ->extraInputAttributes([
                    'capture' => 'environment',
                ])
                ->extraAlpineAttributes([
                    'x-init' => <<<'JS'
const captureFixTimer = setInterval(() => {
    if (! pond || !$refs.input) {
        return;
    }

    $refs.input.setAttribute('accept', 'image/*');
    $refs.input.setAttribute('capture', 'environment');

    clearInterval(captureFixTimer);
}, 50);
JS,
                ]);
        };

        return $form->schema([
            Forms\Components\Select::make('vehiculo_id')
            ->label('Vehículo')
            ->options(function () {

                $user = Auth::user();

                $queryBase = Vehiculo::query()
                    ->whereHas('tarjetas', function ($q) {
                        $q->where('activo', true);
                    });

                if ($user->hasAnyRole(['admin', 'activos', 'fondeo'])) {
                    return $queryBase
                        ->orderBy('numero_economico')
                        ->orderBy('placas')
                        ->get()
                        ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->display_name]);
                }

                return $queryBase
                    ->whereIn('id', FlotaScope::idsVehiculosUsuario())
                    ->orderBy('numero_economico')
                    ->orderBy('placas')
                    ->get()
                    ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->display_name]);
            })
            ->searchable()
            ->live()   // 👈 IMPORTANTE
            ->required()

            ->afterStateUpdated(function ($state, callable $set) {

                if (!$state) return;

                $vehiculo = Vehiculo::query()
                    ->with(['cuentaAnaliticaActiva'])
                    ->find($state);

                // 🔵 Sugerir cuenta analítica del vehículo
                if ($vehiculo?->cuentaAnaliticaActiva?->cuenta_analitica_id) {

                    $set(
                        'cuenta_analitica_id',
                        $vehiculo->cuentaAnaliticaActiva->cuenta_analitica_id
                    );
                }

                
            }),

            Forms\Components\DateTimePicker::make('fecha_carga')
                ->label('Fecha de carga')
                ->default(now())
                ->disabled($esChoferEstricto)
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y h:i A')
                ->format('Y-m-d H:i:s')
                ->helperText($esChoferEstricto
                    ? 'La fecha se asigna automáticamente al guardar para usuarios chofer.'
                    : 'Puedes ajustar la fecha de la carga cuando sea necesario.'),

            Forms\Components\TextInput::make('km_odometro')
                ->label('Kilometraje (odómetro)')
                ->numeric()
                ->required()
                ->rules([
                    fn ($get, ?Model $record) => function ($attribute, $value, $fail) use ($get, $record) {
                        $vehiculoId = $get('vehiculo_id');
                        $fechaCarga = $get('fecha_carga');

                        if (! $vehiculoId || ! $fechaCarga) {
                            return;
                        }

                        $recordId = $record?->id;

                        $cargaAnterior = CargaCombustible::query()
                            ->where('vehiculo_id', $vehiculoId)
                            ->when($recordId, fn (Builder $query) => $query->whereKeyNot($recordId))
                            ->where(function (Builder $query) use ($fechaCarga, $recordId) {
                                $query->where('fecha_carga', '<', $fechaCarga);

                                if ($recordId) {
                                    $query->orWhere(function (Builder $subQuery) use ($fechaCarga, $recordId) {
                                        $subQuery->where('fecha_carga', $fechaCarga)
                                            ->where('id', '<', $recordId);
                                    });
                                } else {
                                    $query->orWhere('fecha_carga', $fechaCarga);
                                }
                            })
                            ->orderedChronologicallyDesc()
                            ->first();

                        if ($cargaAnterior && (int) $value <= (int) $cargaAnterior->km_odometro) {
                            $fail('El kilometraje debe ser mayor al de la carga anterior en la secuencia histórica.');
                            return;
                        }

                        $cargaSiguiente = CargaCombustible::query()
                            ->where('vehiculo_id', $vehiculoId)
                            ->when($recordId, fn (Builder $query) => $query->whereKeyNot($recordId))
                            ->where(function (Builder $query) use ($fechaCarga, $recordId) {
                                $query->where('fecha_carga', '>', $fechaCarga);

                                if ($recordId) {
                                    $query->orWhere(function (Builder $subQuery) use ($fechaCarga, $recordId) {
                                        $subQuery->where('fecha_carga', $fechaCarga)
                                            ->where('id', '>', $recordId);
                                    });
                                }
                            })
                            ->orderedChronologically()
                            ->first();

                        if ($cargaSiguiente && (int) $value >= (int) $cargaSiguiente->km_odometro) {
                            $fail('El kilometraje debe ser menor al de la siguiente carga en la secuencia histórica.');
                        }
                    }
                ]),

            Forms\Components\TextInput::make('litros')
                ->label('Litros exactos del ticket')
                ->numeric()
                ->required()
                ->extraInputAttributes([
                    'step' => '0.001',
                    'inputmode' => 'decimal',
                ])
                ->rule('gt:0')
                ->helperText('Captura los litros exactos que aparecen en el ticket. Puedes registrar hasta 3 decimales.'),

            Forms\Components\TextInput::make('precio_litro')
            ->label('Precio por litro del ticket')
            ->numeric()
            ->required()
            ->rule('gt:0')
            ->helperText('Precio mostrado en el ticket. Se conserva solo como referencia/auditoría.'),

            Forms\Components\TextInput::make('importe')
                ->label('Importe total del ticket')
                ->numeric()
                ->required()
                ->rule('gt:0')
                ->helperText('Captura el total real pagado en el ticket. Este será el monto oficial para movimientos y reportes.'),

            Forms\Components\Select::make('cuenta_analitica_id')
                ->label('Cuenta Analítica')
                ->relationship('cuentaAnalitica', 'nombre')
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn () =>
                    (auth()->user()?->can('carga-combustible.update') || auth()->user()?->can('carga-combustible.update-own-assignment'))
                    && auth()->user()->hasAnyRole(['admin','responsable']))
                ->helperText('Se sugiere automáticamente según el vehículo seleccionado.'),

            $mobileImageUpload('foto_odometro_path', 'Foto odómetro', 'cargas/odometro', fn (?Model $record): bool => blank($record), $esChoferEstricto)
                ->helperText(fn (?Model $record): string => filled($record)
                    ? 'Deja la foto actual si no necesitas cambiarla.'
                    : 'Sube la foto del odómetro para guardar la carga.'),

            $mobileImageUpload('foto_ticket_path', 'Foto ticket', 'cargas/ticket', fn (?Model $record): bool => blank($record), $esChoferEstricto)
                ->helperText(fn (?Model $record): string => filled($record)
                    ? 'Deja la foto actual si no necesitas cambiarla.'
                    : 'Sube la foto del ticket para guardar la carga.'),

            $mobileImageUpload('foto_bomba_path', 'Foto bomba', 'cargas/bomba', fn (?Model $record): bool => blank($record), $esChoferEstricto)
                ->helperText(fn (?Model $record): string => filled($record)
                    ? 'Deja la foto actual si no necesitas cambiarla.'
                    : 'Sube la foto de la bomba para guardar la carga.'),

            Forms\Components\Toggle::make('es_extemporanea')
                ->label('Carga extemporánea')
                ->disabled()
                ->dehydrated(false)
                ->visible(fn (?Model $record) => filled($record)),

            Forms\Components\Textarea::make('motivo_correccion')
                ->label('Motivo de corrección')
                ->disabled()
                ->dehydrated(false)
                ->rows(3)
                ->columnSpanFull()
                ->visible(fn (?Model $record) => filled($record) && (bool) $record?->es_extemporanea),

            Forms\Components\Placeholder::make('registrada_por_auditoria')
                ->label('Registrada por')
                ->content(fn (?Model $record): string => $record?->registradaPor?->name ?? '—')
                ->visible(fn (?Model $record) => filled($record) && (bool) $record?->es_extemporanea),

            Forms\Components\Placeholder::make('fecha_registro_real_auditoria')
                ->label('Fecha de registro administrativo')
                ->content(fn (?Model $record): string => $record?->fecha_registro_real?->format('d/m/Y h:i A') ?? '—')
                ->visible(fn (?Model $record) => filled($record) && (bool) $record?->es_extemporanea),

            
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Placas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_carga')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('km_odometro')->sortable(),
                Tables\Columns\TextColumn::make('litros')->sortable(),
                Tables\Columns\IconColumn::make('es_extemporanea')
                    ->label('Ext.')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('motivo_correccion')
                    ->label('Motivo corrección')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('registradaPor.name')
                    ->label('Registrada por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_registro_real')
                    ->label('Registro administrativo')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('carga-combustible.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('carga-combustible.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('carga-combustible.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->can('carga-combustible.update')) return true;

        if ($user->can('carga-combustible.update-own-assignment') && $user->hasRole('responsable')) {
            return $record->vehiculo->responsableActivo?->responsable_user_id === $user->id;
        }

        return false;
    }
    

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('activos')) {
            return $query;
        }

        if ($user->hasRole('fondeo')) {
            return $query;
        }

        return $query->whereIn('vehiculo_id', FlotaScope::idsVehiculosUsuario());
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
