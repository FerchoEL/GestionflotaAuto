<?php

namespace Tests\Feature;

use App\Filament\Resources\ResponsableAuxiliarResource;
use App\Filament\Resources\ResponsableAuxiliarResource\Pages\CreateResponsableAuxiliar;
use App\Models\ResponsableAuxiliar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ResponsableAuxiliarResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_y_reactivar_una_relacion_sin_modificar_roles(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole('responsable');
        $auxiliar = $this->userWithRole(['chofer', 'auxiliar_responsable']);
        $this->actingAs($admin);

        $record = $this->invokeCreate([
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $auxiliar->id,
            'activo' => true,
        ]);

        $this->assertTrue(ResponsableAuxiliarResource::canViewAny());
        $this->assertSame($admin->id, $record->asignado_por_user_id);
        $this->assertTrue($auxiliar->fresh()->hasRole('chofer'));
        $this->assertTrue($auxiliar->fresh()->hasRole('auxiliar_responsable'));

        $record->update(['activo' => false]);
        $reactivated = $this->invokeCreate([
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $auxiliar->id,
            'activo' => true,
        ]);

        $this->assertSame($record->id, $reactivated->id);
        $this->assertTrue($reactivated->activo);
        $this->assertSame($admin->id, $reactivated->asignado_por_user_id);
        $this->assertSame(1, ResponsableAuxiliar::query()->count());
    }

    public function test_solo_un_responsable_puede_ser_seleccionado_como_responsable(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $activos = $this->userWithRole('activos');
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $this->actingAs($admin);

        $this->assertTrue(ResponsableAuxiliarResource::responsableUsersQuery()->whereKey($this->userWithRole('responsable'))->exists());
        $this->assertFalse(ResponsableAuxiliarResource::responsableUsersQuery()->whereKey($activos)->exists());

        $this->expectException(ValidationException::class);
        $this->invokeCreate([
            'responsable_user_id' => $activos->id,
            'auxiliar_user_id' => $auxiliar->id,
            'activo' => true,
        ]);
    }

    public function test_el_auxiliar_debe_tener_previamente_el_rol_correspondiente_y_no_se_asigna_automaticamente(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole('responsable');
        $sinRolAuxiliar = $this->userWithRole('chofer');
        $this->actingAs($admin);

        $this->assertFalse(ResponsableAuxiliarResource::auxiliaryUsersQuery($responsable->id)->whereKey($sinRolAuxiliar)->exists());

        $this->expectException(ValidationException::class);
        $this->invokeCreate([
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $sinRolAuxiliar->id,
            'activo' => true,
        ]);
    }

    public function test_el_auxiliar_seleccionado_se_excluye_al_responsable(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole(['responsable', 'auxiliar_responsable']);
        $this->actingAs($admin);

        $this->assertFalse(ResponsableAuxiliarResource::auxiliaryUsersQuery($responsable->id)->whereKey($responsable)->exists());
    }

    public function test_no_permite_autorrelacion_ni_duplicado_activo(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole('responsable');
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        $this->invokeCreate([
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $responsable->id,
            'activo' => true,
        ]);
    }

    public function test_no_permite_duplicar_una_relacion_activa(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole('responsable');
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $this->actingAs($admin);

        $data = [
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $auxiliar->id,
            'activo' => true,
        ];
        $this->invokeCreate($data);

        $this->expectException(ValidationException::class);
        $this->invokeCreate($data);
    }

    public function test_solo_admin_puede_acceder_y_no_se_modifican_asignaciones_formales(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $responsable = $this->userWithRole('responsable');
        $auxiliar = $this->userWithRole('auxiliar_responsable');

        $this->actingAs($admin);
        $this->assertTrue(ResponsableAuxiliarResource::canViewAny());
        $this->assertTrue(ResponsableAuxiliarResource::canCreate());

        $this->actingAs($responsable);
        $this->assertFalse(ResponsableAuxiliarResource::canViewAny());

        $this->actingAs($auxiliar);
        $this->assertFalse(ResponsableAuxiliarResource::canViewAny());
        $this->assertSame(0, $this->app['db']->table('vehiculo_responsables')->count());
    }

    private function invokeCreate(array $data): ResponsableAuxiliar
    {
        $page = app(CreateResponsableAuxiliar::class);
        $method = new ReflectionMethod($page, 'handleRecordCreation');
        $method->setAccessible(true);

        /** @var ResponsableAuxiliar $record */
        $record = $method->invoke($page, $data);

        return $record;
    }

    private function userWithRole(string|array $roles): User
    {
        $user = User::factory()->create();
        $user->assignRole($roles);

        return $user;
    }
}
