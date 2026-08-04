<?php

namespace Tests\Feature;

use App\Filament\Pages\FondeoDashboard;
use App\Filament\Pages\FondeoFinancieroDashboard;
use App\Filament\Pages\MisVehiculos;
use App\Filament\Pages\ReporteCombustible;
use App\Filament\Pages\ReporteDocumentos;
use App\Filament\Resources\CargaCombustibleResource;
use App\Filament\Resources\FondeoResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\TipoDocumentoResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VehiculoDocumentoResource;
use App\Filament\Resources\VehiculoResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Stage4AAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_all_representative_sections(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->assertTrue(CargaCombustibleResource::canViewAny());
        $this->assertTrue(VehiculoResource::canViewAny());
        $this->assertTrue(RoleResource::canViewAny());
        $this->assertTrue(FondeoDashboard::canAccess());
        $this->assertTrue(ReporteDocumentos::canAccess());
    }

    public function test_chofer_can_access_only_mis_vehiculos_and_mis_cargas(): void
    {
        $this->actingAs($this->userWithRole('chofer'));

        $this->assertTrue(MisVehiculos::canAccess());
        $this->assertTrue(CargaCombustibleResource::canViewAny());

        foreach ([
            ReporteCombustible::class,
            ReporteDocumentos::class,
            VehiculoDocumentoResource::class,
            VehiculoResource::class,
            TipoDocumentoResource::class,
            UserResource::class,
            RoleResource::class,
        ] as $protectedComponent) {
            $canAccess = method_exists($protectedComponent, 'canAccess')
                ? $protectedComponent::canAccess()
                : $protectedComponent::canViewAny();

            $this->assertFalse($canAccess, "Chofer no debe acceder a {$protectedComponent}.");
        }
    }

    public function test_responsable_and_fondeo_keep_only_their_approved_sections(): void
    {
        $this->actingAs($this->userWithRole('responsable'));
        $this->assertTrue(MisVehiculos::canAccess());
        $this->assertTrue(CargaCombustibleResource::canViewAny());
        $this->assertTrue(ReporteCombustible::canAccess());
        $this->assertTrue(ReporteDocumentos::canAccess());
        $this->assertTrue(VehiculoDocumentoResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());

        $this->actingAs($this->userWithRole('fondeo'));
        $this->assertTrue(FondeoDashboard::canAccess());
        $this->assertTrue(FondeoFinancieroDashboard::canAccess());
        $this->assertTrue(FondeoResource::canViewAny());
        $this->assertFalse(ReporteDocumentos::canAccess());
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_users_without_view_permission_are_denied_by_url(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->actingAs(User::factory()->create());
        config(['app.env' => 'local']);

        $this->get(CargaCombustibleResource::getUrl())->assertForbidden();
        $this->get(ReporteDocumentos::getUrl())->assertForbidden();
    }

    public function test_administracion_with_zero_permissions_cannot_access_protected_sections(): void
    {
        $this->actingAs($this->userWithRole('administracion'));

        $this->assertFalse(MisVehiculos::canAccess());
        $this->assertFalse(CargaCombustibleResource::canViewAny());
        $this->assertFalse(FondeoResource::canViewAny());
        $this->assertFalse(RoleResource::canViewAny());
    }

    private function userWithRole(string $role): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
