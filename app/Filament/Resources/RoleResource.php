<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;


class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Roles';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Role $record): bool => $record?->name === 'admin')
                    ->dehydrated(fn (?Role $record): bool => $record?->name !== 'admin'),
                Forms\Components\Section::make('Permisos')
                    ->description('Selecciona los permisos del rol por sección. Los nombres técnicos se conservan internamente.')
                    ->schema(static::permissionGroupFields())
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permisos'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $deletable = $records->filter(
                                fn (Role $role): bool => static::canDelete($role),
                            );

                            $deletable->each->delete();

                            if ($deletable->count() !== $records->count()) {
                                Notification::make()
                                    ->warning()
                                    ->title('El rol admin no puede eliminarse')
                                    ->body('Los demás roles seleccionados sí fueron procesados.')
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('rol.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny()
            && (bool) auth()->user()?->can('rol.create');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny()
            && (bool) auth()->user()?->can('rol.update');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return $record->name !== 'admin'
            && static::canViewAny()
            && (bool) auth()->user()?->can('rol.delete');
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function permissionOptionsByGroup(): array
    {
        $catalog = config('permissions.catalog');
        $groups = [
            'modules' => $catalog['modules'],
            'pages' => $catalog['pages'],
            'resources' => collect($catalog['resources'])
                ->flatMap(fn (string $resource): array => [
                    "{$resource}.view",
                    "{$resource}.create",
                    "{$resource}.update",
                    "{$resource}.delete",
                ])
                ->all(),
            'operations' => $catalog['operations'],
        ];

        return collect($groups)
            ->mapWithKeys(fn (array $permissions, string $group): array => [
                $group => collect($permissions)
                    ->mapWithKeys(fn (string $permission): array => [
                        $permission => static::permissionLabel($permission),
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function catalogPermissionNames(): array
    {
        return collect(static::permissionOptionsByGroup())
            ->flatMap(fn (array $permissions): array => array_keys($permissions))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function permissionGroupsFromData(array $data): array
    {
        $allowed = array_flip(static::catalogPermissionNames());
        $groups = $data['permission_groups'] ?? [];

        return collect($groups)
            ->map(fn (mixed $permissions): array => collect(is_array($permissions) ? $permissions : [])
                ->filter(fn (mixed $permission): bool => is_string($permission) && isset($allowed[$permission]))
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function permissionGroupsForRole(Role $role): array
    {
        $selectedPermissions = $role->permissions()->pluck('name')->all();

        return collect(static::permissionOptionsByGroup())
            ->map(fn (array $options): array => array_values(array_intersect(
                array_keys($options),
                $selectedPermissions,
            )))
            ->all();
    }

    public static function syncPermissionsFor(Role $role, array $data): void
    {
        if ($role->name === 'admin') {
            $role->syncPermissions(static::catalogPermissionNames());

            Notification::make()
                ->warning()
                ->title('Los permisos de admin están protegidos')
                ->body('El rol admin conserva todos los permisos del catálogo.')
                ->send();

            return;
        }

        $selected = collect(static::permissionGroupsFromData($data))
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $user = auth()->user();
        if ($user?->hasRole($role->name)) {
            $selected = array_values(array_unique(array_merge($selected, [
                'rol.view',
                'rol.create',
                'rol.update',
                'rol.delete',
            ])));
        }

        $role->syncPermissions($selected);
    }

    protected static function permissionLabel(string $permission): string
    {
        $labels = [
            'view' => 'Ver',
            'create' => 'Crear',
            'update' => 'Editar',
            'delete' => 'Eliminar',
            'export' => 'Exportar',
            'fondear' => 'Fondear',
            'retirar' => 'Retirar',
            'transferir' => 'Transferir',
            'create-extemporanea' => 'Crear extemporánea',
            'update-own-assignment' => 'Editar asignaciones propias',
            'update-own' => 'Editar propios',
        ];

        $action = Str::afterLast($permission, '.');
        $subject = Str::beforeLast($permission, '.');

        return sprintf(
            '%s · %s',
            Str::headline($subject),
            $labels[$action] ?? Str::headline($action),
        );
    }

    /**
     * @return array<int, Forms\Components\Section>
     */
    protected static function permissionGroupFields(): array
    {
        $labels = [
            'modules' => 'Módulos',
            'pages' => 'Páginas',
            'resources' => 'Recursos',
            'operations' => 'Operaciones',
        ];

        return collect(static::permissionOptionsByGroup())
            ->map(fn (array $options, string $group): Forms\Components\Section => Forms\Components\Section::make($labels[$group] ?? Str::headline($group))
                ->schema([
                    Forms\Components\CheckboxList::make("permission_groups.{$group}")
                        ->label('Permisos disponibles')
                        ->options($options)
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3)
                        ->disabled(fn (?Role $record): bool => $record?->name === 'admin'),
                ])
                ->collapsible()
                ->collapsed($group === 'resources'))
            ->values()
            ->all();
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
