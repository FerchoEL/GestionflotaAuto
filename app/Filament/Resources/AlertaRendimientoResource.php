<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertaRendimientoResource\Pages;
use App\Filament\Resources\AlertaRendimientoResource\RelationManagers;
use App\Models\AlertaRendimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model; 

class AlertaRendimientoResource extends Resource
{
    protected static ?string $model = AlertaRendimiento::class;

    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Alertas de rendimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                ->label('Comentario de auditoría')
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('fecha_cierre')
                ->label('Fecha de cierre')
                ->disabled()
                ->dehydrated(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')->label('Vehículo'),

            Tables\Columns\TextColumn::make('vehiculo.placas')
                ->label('Vehículo')
                ->searchable(),

            Tables\Columns\TextColumn::make('responsable.name')
                ->label('Responsable')
                ->toggleable(),

            Tables\Columns\TextColumn::make('rendimiento_detectado')
                ->label('Rendimiento Detectado')
                ->sortable(),

            Tables\Columns\TextColumn::make('rendimiento_optimo')
                ->label('Rendimiento Óptimo')
                ->toggleable(),

            Tables\Columns\TextColumn::make('umbral_aplicado')
                ->label('Umbral')
                ->toggleable(),

            Tables\Columns\BadgeColumn::make('estatus')
                ->colors([
                    'danger' => 'Abierta',
                    'success' => 'Cerrada',
                ]),

            Tables\Columns\TextColumn::make('fecha_alerta')
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('fecha_cierre')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_alerta', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlertaRendimientos::route('/'),
            'create' => Pages\CreateAlertaRendimiento::route('/create'),
            'edit' => Pages\EditAlertaRendimiento::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'responsable',
            'activos'
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('responsable')) {
            return $record->responsable_user_id === $user->id;
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

        if ($user->hasRole('responsable')) {
            return $query->where('responsable_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
