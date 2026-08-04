<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_role_management_permission_can_access_role_list_and_edit(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user);

        $this->assertTrue(RoleResource::canViewAny());
        $this->assertTrue(RoleResource::canEdit(Role::findByName('activos')));
        config(['app.env' => 'local']);
        $this->get(RoleResource::getUrl())->assertSuccessful();
    }

    public function test_user_without_role_management_permission_cannot_access_roles_or_direct_url(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(RoleResource::canViewAny());
        config(['app.env' => 'local']);
        $this->get(RoleResource::getUrl())->assertForbidden();
    }

    public function test_role_permissions_are_loaded_from_spatie_and_can_be_updated_without_duplicates(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $role = Role::findByName('administracion');
        $data = [
            'permission_groups' => [
                'modules' => ['modulo.combustible.view'],
                'pages' => ['pagina.mis-vehiculos.view'],
            ],
        ];

        RoleResource::syncPermissionsFor($role, $data);
        RoleResource::syncPermissionsFor($role, $data);

        $groups = RoleResource::permissionGroupsForRole($role->fresh());
        $this->assertContains('modulo.combustible.view', $groups['modules']);
        $this->assertContains('pagina.mis-vehiculos.view', $groups['pages']);
        $this->assertSame([
            'modulo.combustible.view',
            'pagina.mis-vehiculos.view',
        ], $role->fresh()->permissions()->pluck('name')->sort()->values()->all());
        $this->assertSame(1, $role->fresh()->permissions()->where('name', 'modulo.combustible.view')->count());
        $this->assertSame(0, $user->fresh()->permissions()->count());
    }

    public function test_admin_cannot_be_deleted_or_renamed_and_keeps_all_catalog_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $admin = Role::findByName('admin');
        RoleResource::syncPermissionsFor($admin, ['permission_groups' => []]);

        $this->assertFalse(RoleResource::canDelete($admin));
        $this->assertSame(134, $admin->fresh()->permissions()->count());
        $this->assertSame('admin', $admin->fresh()->name);
    }

    public function test_administracion_remains_visible_with_zero_permissions_until_explicitly_changed(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $role = Role::findByName('administracion');

        $this->assertTrue($role->exists);
        $this->assertSame(0, $role->permissions()->count());
        $this->assertArrayHasKey('modules', RoleResource::permissionOptionsByGroup());
        $this->assertArrayHasKey('modulo.combustible.view', RoleResource::permissionOptionsByGroup()['modules']);
    }
}
