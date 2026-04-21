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
use Illuminate\Database\Eloquent\Model;


class CargaCombustibleResource extends Resource
{
    protected static ?string $model = CargaCombustible::class;

    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Carga de combustible';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vehiculo_id')
            ->label('Vehículo')
            ->options(function () {

                $user = Auth::user();

                $queryBase = Vehiculo::query()
                    ->whereHas('responsables', function ($q) {
                        $q->where('activo', true);
                    })
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

                $idsChofer = VehiculoChofer::where('chofer_user_id', $user->id)
                    ->where('activo', true)
                    ->pluck('vehiculo_id');

                $idsResp = VehiculoResponsable::where('responsable_user_id', $user->id)
                    ->where('activo', true)
                    ->pluck('vehiculo_id');

                $ids = $idsChofer->merge($idsResp)->unique()->values();

                return $queryBase
                    ->whereIn('id', $ids)
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
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y h:i A')
                ->format('Y-m-d H:i:s'),

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
                ->numeric()
                ->required()
                ->rule('gt:0'),

            Forms\Components\TextInput::make('precio_litro')
            ->numeric()
            ->required()
            ->reactive(),

            Forms\Components\TextInput::make('importe')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(function ($get) {
                    if ($get('litros') && $get('precio_litro')) {
                        return round($get('litros') * $get('precio_litro'), 2);
                    }
                    return 0;
                }),

            Forms\Components\Select::make('cuenta_analitica_id')
                ->label('Cuenta Analítica')
                ->relationship('cuentaAnalitica', 'nombre')
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn () => auth()->user()->hasAnyRole(['admin','responsable']))
                ->helperText('Se sugiere automáticamente según el vehículo seleccionado.'),

            Forms\Components\FileUpload::make('foto_odometro_path')
                ->label('Foto odómetro')
                ->image()
                ->required()
                ->acceptedFileTypes(['image/*'])
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ])
                ->disk('public')
                ->directory('cargas/odometro')
                ->maxSize(20480),

            Forms\Components\FileUpload::make('foto_ticket_path')
                ->label('Foto ticket')
                ->image()
                ->required()
                ->acceptedFileTypes(['image/*'])
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ])
                ->disk('public')
                ->directory('cargas/ticket')
                ->maxSize(20480),

            Forms\Components\FileUpload::make('foto_bomba_path')
                ->label('Foto bomba')
                ->image()
                ->required()
                ->acceptedFileTypes(['image/*'])
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ])
                ->disk('public')
                ->directory('cargas/bomba')
                ->maxSize(20480),

            
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
        return auth()->user()->hasAnyRole([
            'admin',
            'chofer',
            'responsable',
            'activos'
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'chofer'
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('responsable')) {
            return $record->vehiculo->responsableActivo?->responsable_user_id === $user->id;
        }

        return false;
    }
    

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('activos')) {
            return $query;
        }

        if ($user->hasRole('chofer')) {
            return $query->whereHas('vehiculo.choferes', function ($q) use ($user) {
                $q->where('chofer_user_id', $user->id)
                ->where('activo', true);
            });
        }

        if ($user->hasRole('responsable')) {
            return $query->whereHas('vehiculo.responsables', function ($q) use ($user) {
                $q->where('responsable_user_id', $user->id)
                ->where('activo', true);
            });
        }

        return $query->whereRaw('1 = 0');
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
