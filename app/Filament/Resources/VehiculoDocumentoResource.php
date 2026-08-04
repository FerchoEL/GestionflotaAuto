<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoDocumentoResource\Pages;
use App\Models\Aseguradora;
use App\Models\TipoDocumento;
use App\Models\TipoPago;
use App\Models\Vehiculo;
use App\Models\VehiculoDocumento;
use App\Support\FlotaScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class VehiculoDocumentoResource extends Resource
{
    protected static ?string $model = VehiculoDocumento::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Documentación';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Documentos por vehículo';
    protected static ?string $modelLabel = 'Documento por vehículo';
    protected static ?string $pluralModelLabel = 'Documentos por vehículo';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('vehiculo_id')
                ->options(fn () => Vehiculo::query()
                    ->orderBy('numero_economico')
                    ->orderBy('placas')
                    ->get()
                    ->mapWithKeys(fn (Vehiculo $record): array => [
                        $record->id => $record->display_name !== '' ? $record->display_name : 'Vehículo sin identificación',
                    ]))
                ->searchable(['numero_economico', 'placas'])
                ->preload()
                ->required()
                ->label('Vehículo'),

            Select::make('tipo_documento_id')
                ->options(fn () => TipoDocumento::query()
                    ->orderBy('nombre')
                    ->get()
                    ->mapWithKeys(fn (TipoDocumento $record): array => [
                        $record->id => filled($record->nombre) ? $record->nombre : 'Tipo de documento sin nombre',
                    ]))
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->label('Tipo de documento'),

            TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->label('Nombre'),

            FileUpload::make('archivo_path')
                ->disk('public')
                ->directory('vehiculos/documentos')
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/jpg',
                ])
                ->maxSize(10240)
                ->downloadable()
                ->openable()
                ->required()
                ->label('Archivo'),

            DatePicker::make('fecha_emision')
                ->label('Fecha de emisión'),

            DatePicker::make('fecha_vencimiento')
                ->label('Fecha de vencimiento')
                ->visible(fn (Get $get): bool => static::tipoDocumentoRequiereVigencia($get('tipo_documento_id')))
                ->required(fn (Get $get): bool => static::tipoDocumentoRequiereVigencia($get('tipo_documento_id'))),

            // Sección específica para pólizas de seguro
            Section::make('Información de la Póliza de Seguro')
                ->visible(fn (Get $get): bool => static::esPolizaSeguro($get('tipo_documento_id')))
                ->schema([
                    Select::make('poliza_aseguradora_id')
                        ->options(fn () => Aseguradora::where('activo', true)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id'))
                        ->searchable()
                        ->preload()
                        ->label('Aseguradora')
                        ->required(fn (Get $get): bool => static::esPolizaSeguro($get('tipo_documento_id'))),

                    TextInput::make('poliza_costo')
                        ->numeric()
                        ->prefix('$')
                        ->step('0.01')
                        ->label('Costo de la póliza')
                        ->required(fn (Get $get): bool => static::esPolizaSeguro($get('tipo_documento_id'))),

                    Select::make('poliza_tipo_pago_id')
                        ->options(fn () => TipoPago::orderBy('nombre')
                            ->pluck('nombre', 'id'))
                        ->searchable()
                        ->preload()
                        ->label('Tipo de pago')
                        ->required(fn (Get $get): bool => static::esPolizaSeguro($get('tipo_documento_id'))),

                    Textarea::make('poliza_notas')
                        ->maxLength(500)
                        ->rows(3)
                        ->label('Notas'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = auth()->user();

                $query->with(['vehiculo', 'tipoDocumento']);

                if ($user->hasRole('admin') || $user->hasRole('activos')) {
                    return $query;
                }

                return $query->whereIn('vehiculo_id', FlotaScope::idsVehiculosUsuario());
            })
            ->columns([
                TextColumn::make('vehiculo.numero_economico')
                    ->label('No. Económico')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(TextColumnSize::Small),

                TextColumn::make('vehiculo.placas')
                    ->label('Placas')
                    ->searchable()
                    ->sortable()
                    ->size(TextColumnSize::Small),

                TextColumn::make('tipoDocumento.nombre')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('nombre')
                    ->label('Documento guardado')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fecha_emision')
                    ->label('Emisión')
                    ->date('d/m/Y')
                    ->placeholder('Sin captura'),

                TextColumn::make('fecha_vencimiento')
                    ->label('Vigencia')
                    ->date('d/m/Y')
                    ->placeholder('Sin vigencia')
                    ->badge()
                    ->color(fn (VehiculoDocumento $record): string => $record->colorEstadoVigencia()),
            ])
            ->filters(
                auth()->user()->hasAnyRole(['admin', 'activos'])
                    ? [
                        Tables\Filters\SelectFilter::make('vehiculo_id')
                            ->options(fn () => Vehiculo::query()
                                ->orderBy('numero_economico')
                                ->orderBy('placas')
                                ->get()
                                ->mapWithKeys(fn (Vehiculo $record): array => [
                                    $record->id => $record->display_name !== '' ? $record->display_name : 'Vehículo sin identificación',
                                ]))
                            ->label('Vehículo'),

                        Tables\Filters\SelectFilter::make('tipo_documento_id')
                            ->options(fn () => TipoDocumento::query()
                                ->orderBy('nombre')
                                ->get()
                                ->mapWithKeys(fn (TipoDocumento $record): array => [
                                    $record->id => filled($record->nombre) ? $record->nombre : 'Tipo de documento sin nombre',
                                ]))
                            ->label('Tipo de documento'),
                    ]
                    : []
            )
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('abrir_documento')
                    ->label('Abrir documento')
                    ->icon('heroicon-o-eye')
                    ->url(fn (VehiculoDocumento $record): string => Storage::disk('public')->url($record->archivo_path))
                    ->openUrlInNewTab()
                    ->authorize(fn () => auth()->user()?->can('vehiculo-documento.view') ?? false),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('vehiculo-documento.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('vehiculo-documento.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('vehiculo-documento.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('vehiculo-documento.update') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculoDocumentos::route('/'),
            'create' => Pages\CreateVehiculoDocumento::route('/create'),
            'edit' => Pages\EditVehiculoDocumento::route('/{record}/edit'),
        ];
    }

    protected static function tipoDocumentoRequiereVigencia(?string $tipoDocumentoId): bool
    {
        if (! filled($tipoDocumentoId)) {
            return false;
        }

        return (bool) TipoDocumento::query()
            ->whereKey($tipoDocumentoId)
            ->value('requiere_vigencia');
    }

    protected static function esPolizaSeguro(?string $tipoDocumentoId): bool
    {
        if (! filled($tipoDocumentoId)) {
            return false;
        }

        return (bool) TipoDocumento::query()
            ->whereKey($tipoDocumentoId)
            ->value('es_poliza_seguro');
    }
}
