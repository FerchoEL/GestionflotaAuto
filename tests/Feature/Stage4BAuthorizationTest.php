<?php

namespace Tests\Feature;

use App\Filament\Pages\ReporteCombustible;
use App\Filament\Pages\ReporteDocumentos;
use App\Filament\Resources\CargaCombustibleResource;
use App\Filament\Resources\FondeoResource;
use App\Filament\Resources\TarjetaCombustibleResource;
use App\Filament\Resources\VehiculoResource;
use App\Models\CargaCombustible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Stage4BAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_actions_use_existing_permissions(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(CargaCombustibleResource::canCreate());
        $this->assertTrue(CargaCombustibleResource::canEdit(new CargaCombustible));
        $this->assertTrue(CargaCombustibleResource::canDelete(new CargaCombustible));
        $this->assertTrue(CargaCombustibleResource::canDeleteAny());
        $this->assertTrue(VehiculoResource::canCreate());

        $fondeo = $this->userWithRole('fondeo');
        $this->actingAs($fondeo);
        $this->assertTrue(FondeoResource::canCreate());
        $this->assertFalse(FondeoResource::canDelete(new \App\Models\Fondeo));
        $this->assertFalse(TarjetaCombustibleResource::canCreate());
    }

    public function test_report_exports_require_the_approved_export_permissions(): void
    {
        $responsable = $this->userWithRole('responsable');
        $this->actingAs($responsable);

        $this->assertTrue($responsable->can('reporte-combustible.export'));
        $this->assertTrue($responsable->can('reporte-documentos.export'));

        $administracion = $this->userWithRole('administracion');
        $this->actingAs($administracion);
        $this->assertFalse($administracion->can('reporte-combustible.export'));
        $this->assertFalse(ReporteCombustible::canAccess());
        $this->assertFalse(ReporteDocumentos::canAccess());
    }

    public function test_financial_custom_actions_match_their_existing_permissions(): void
    {
        $fondeo = $this->userWithRole('fondeo');
        $this->actingAs($fondeo);

        $this->assertTrue($fondeo->can('fondeo-financiero.fondear'));
        $this->assertTrue($fondeo->can('fondeo-financiero.retirar'));
        $this->assertTrue($fondeo->can('fondeo-financiero.transferir'));

        $chofer = $this->userWithRole('chofer');
        $this->assertFalse($chofer->can('fondeo-financiero.fondear'));
        $this->assertFalse($chofer->can('reporte-combustible.export'));
    }

    private function userWithRole(string $role): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
