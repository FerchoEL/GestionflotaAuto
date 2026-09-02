<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResponsableAuxiliarResource\Pages;
use App\Models\ResponsableAuxiliar;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ResponsableAuxiliarResource extends Resource
{
    protected static ?string $model = ResponsableAuxiliar::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Responsables y auxiliares';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('responsable_user_id')
                ->label('Responsable')
                ->options(fn (): array => static::responsableUsersQuery()
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->live(),

            Forms\Components\Select::make('auxiliar_user_id')
                ->label('Auxiliar')
                ->options(fn (Get $get): array => static::auxiliaryUsersQuery($get('responsable_user_id'))
                    ->get()
                    ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->rule('different:responsable_user_id'),

            Forms\Components\Toggle::make('activo')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('responsable.name')->label('Responsable')->searchable(),
                Tables\Columns\TextColumn::make('auxiliar.name')->label('Auxiliar')->searchable(),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('asignadoPor.name')->label('Asignado por'),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('responsable_user_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name'),
                Tables\Filters\SelectFilter::make('auxiliar_user_id')
                    ->label('Auxiliar')
                    ->relationship('auxiliar', 'name'),
                Tables\Filters\TernaryFilter::make('activo')->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['responsable', 'auxiliar', 'asignadoPor']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function validateDistinctUsers(array $data): void
    {
        if ((int) $data['responsable_user_id'] === (int) $data['auxiliar_user_id']) {
            throw ValidationException::withMessages([
                'auxiliar_user_id' => 'El responsable y el auxiliar deben ser usuarios distintos.',
            ]);
        }
    }

    public static function validateEligibleUsers(array $data): void
    {
        $responsableId = (int) ($data['responsable_user_id'] ?? 0);
        $auxiliarId = (int) ($data['auxiliar_user_id'] ?? 0);

        if (! static::responsableUsersQuery()->whereKey($responsableId)->exists()) {
            throw ValidationException::withMessages([
                'responsable_user_id' => 'El usuario seleccionado debe tener el rol responsable.',
            ]);
        }

        if (! static::auxiliaryUsersQuery($responsableId)->whereKey($auxiliarId)->exists()) {
            throw ValidationException::withMessages([
                'auxiliar_user_id' => 'El usuario seleccionado debe tener previamente el rol auxiliar_responsable.',
            ]);
        }
    }

    public static function responsableUsersQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'responsable'));
    }

    public static function auxiliaryUsersQuery(?int $responsableId = null): Builder
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'auxiliar_responsable'))
            ->when($responsableId, fn (Builder $query) => $query->whereKeyNot($responsableId))
            ->orderBy('name');
    }

    public static function existingRelation(array $data, ?ResponsableAuxiliar $except = null): ?ResponsableAuxiliar
    {
        return ResponsableAuxiliar::query()
            ->where('responsable_user_id', $data['responsable_user_id'])
            ->where('auxiliar_user_id', $data['auxiliar_user_id'])
            ->when($except?->exists, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->first();
    }

    public static function duplicateException(): ValidationException
    {
        return ValidationException::withMessages([
            'auxiliar_user_id' => 'Ya existe una relación activa entre estos usuarios.',
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResponsableAuxiliares::route('/'),
            'create' => Pages\CreateResponsableAuxiliar::route('/create'),
            'edit' => Pages\EditResponsableAuxiliar::route('/{record}/edit'),
        ];
    }
}
