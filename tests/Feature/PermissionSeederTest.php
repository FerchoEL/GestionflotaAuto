<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_complete_central_catalog(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $catalog = collect(config('permissions.catalog'));
        $expected = collect([
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
            ->unique();

        $this->assertSame($expected->count(), Permission::count());
        $this->assertSame($expected->sort()->values()->all(), Permission::query()->pluck('name')->sort()->values()->all());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $permissions = Permission::count();
        $roles = Role::count();
        $adminPermissions = Role::findByName('admin')->permissions()->count();

        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->assertSame($permissions, Permission::count());
        $this->assertSame($roles, Role::count());
        $this->assertSame($adminPermissions, Role::findByName('admin')->permissions()->count());
    }

    public function test_assigns_approved_permissions_by_role(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        foreach (config('permissions.roles') as $roleName => $permissions) {
            $role = Role::findByName($roleName);
            $expected = $permissions === '*'
                ? Permission::query()->pluck('name')->all()
                : $permissions;

            $this->assertSame(
                collect($expected)->sort()->values()->all(),
                $role->permissions()->pluck('name')->sort()->values()->all(),
                "Permisos incorrectos para {$roleName}."
            );
        }
    }

    public function test_admin_has_all_permissions_and_operational_roles_do_not_have_administrative_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $allPermissions = Permission::query()->pluck('name')->sort()->values()->all();
        $this->assertSame($allPermissions, Role::findByName('admin')->permissions()->pluck('name')->sort()->values()->all());

        $responsablePermissions = Role::findByName('responsable')->permissions()->pluck('name');
        $this->assertTrue($responsablePermissions->every(fn (string $permission): bool => ! str_starts_with($permission, 'modulo.configuracion.')
            && ! in_array(strtok($permission, '.'), ['usuario', 'rol'], true)));

        $choferPermissions = Role::findByName('chofer')->permissions()->pluck('name');
        $this->assertTrue($choferPermissions->every(fn (string $permission): bool => ! str_starts_with($permission, 'modulo.configuracion.')
            && ! str_starts_with($permission, 'modulo.documentacion.')
            && ! in_array(strtok($permission, '.'), ['usuario', 'rol'], true)));

        $this->assertSame(0, Role::findByName('administracion')->permissions()->count());
        $this->assertNotNull(Role::findByName('fondeo'));
    }
}
