<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('permissions.catalog');
        $permissionNames = collect([
                ...$catalog['modules'],
                ...$catalog['pages'],
                ...$catalog['operations'],
            ])
            ->merge(collect($catalog['resources'])
                ->flatMap(fn (string $resource): array => [
                    "{$resource}.view",
                    "{$resource}.create",
                    "{$resource}.update",
                    "{$resource}.delete",
                ]))
            ->unique()
            ->values();

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach (config('permissions.roles') as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions === '*'
                ? $permissionNames->all()
                : $permissions);
        }
    }
}
