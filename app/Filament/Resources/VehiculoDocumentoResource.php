<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoDocumentoResource\Pages;
use App\Models\TipoDocumento;
use App\Models\Vehiculo;
use App\Models\VehiculoDocumento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                ->relationship(
                    name: 'vehiculo',
                    titleAttribute: 'numero_economico',
                    modifyQueryUsing: fn ($query) => $query
                        ->orderBy('numero_economico')
                        ->orderBy('placas')
                )
                ->getOptionLabelFromRecordUsing(fn (Vehiculo $record): string => $record->display_name)
                ->searchable(['numero_economico', 'placas'])
                ->preload()
                ->required()
                ->label('Vehículo'),

            Select::make('tipo_documento_id')
                ->relationship('tipoDocumento', 'nombre')
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = auth()->user();
                $userId = $user->id;

                $query->with(['vehiculo', 'tipoDocumento']);

                if ($user->hasRole('admin') || $user->hasRole('activos')) {
                    return $query;
                }

                if ($user->hasRole('chofer')) {
                    return $query->whereHas('vehiculo.choferes', function (Builder $subQuery) use ($userId) {
                        $subQuery
                            ->where('chofer_user_id', $userId)
                            ->where('activo', true)
                            ->where(function (Builder $sub) {
                                $sub->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', now());
                            });
                    });
                }

                if ($user->hasRole('responsable')) {
                    return $query->whereHas('vehiculo.responsableActivo', function (Builder $subQuery) use ($userId) {
                        $subQuery->where('responsable_user_id', $userId);
                    });
                }

                return $query->whereRaw('1 = 0');
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
                            ->relationship('vehiculo', 'numero_economico')
                            ->label('Vehículo'),

                        Tables\Filters\SelectFilter::make('tipo_documento_id')
                            ->relationship('tipoDocumento', 'nombre')
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
                    ->openUrlInNewTab(),
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
        return auth()->user()->hasAnyRole(['admin', 'activos', 'responsable', 'chofer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasRole('admin');
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
}
