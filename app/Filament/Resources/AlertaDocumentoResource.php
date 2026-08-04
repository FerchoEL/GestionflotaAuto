<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertaDocumentoResource\Pages;
use App\Models\AlertaDocumento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AlertaDocumentoResource extends Resource
{
    protected static ?string $model = AlertaDocumento::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationGroup = 'Documentación';
    protected static ?string $navigationLabel = 'Alertas de documentos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('tipo')
                ->label('Tipo')
                ->content(fn (?Model $record): string => match ($record?->tipo) {
                    'vencido' => 'Documento vencido',
                    'por_vencer' => 'Documento por vencer',
                    default => '—',
                }),

            Forms\Components\Select::make('estatus')
                ->options([
                    'Abierta' => 'Abierta',
                    'Cerrada' => 'Cerrada',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'Cerrada') {
                        $set('fecha_cierre', now());
                    }
                }),

            Forms\Components\Textarea::make('comentario')
                ->label('Comentario')
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('fecha_cierre')
                ->disabled()
                ->dehydrated(),
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
                Tables\Columns\TextColumn::make('tipoDocumento.nombre')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'vencido' => 'Vencido',
                        'por_vencer' => 'Por vencer',
                        default => '—',
                    })
                    ->colors([
                        'danger' => 'vencido',
                        'warning' => 'por_vencer',
                    ]),
                Tables\Columns\TextColumn::make('descripcion')
                    ->wrap()
                    ->limit(60),
                Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'danger' => 'Abierta',
                        'success' => 'Cerrada',
                    ]),
                Tables\Columns\TextColumn::make('fecha_alerta')->dateTime()->sortable(),
            ])
            ->defaultSort('fecha_alerta', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlertaDocumentos::route('/'),
            'edit' => Pages\EditAlertaDocumento::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('alerta-documento.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('alerta-documento.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->can('alerta-documento.update')) {
            return true;
        }

        return $user->can('alerta-documento.update-own')
            && $user->hasRole('responsable')
            && $record->responsable_user_id === $user->id;
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

        if ($user->hasRole('responsable')) {
            return $query->where('responsable_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
