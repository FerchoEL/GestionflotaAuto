<?php

namespace Tests\Feature;

use App\Filament\Resources\SolicitudCargaCombustibleResource;
use App\Models\CargaCombustible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudCargaCombustibleAuxiliarTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsable_y_auxiliar_pueden_editar_la_cuenta_analitica_de_sus_solicitudes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        foreach (['responsable', 'auxiliar_responsable'] as $role) {
            $this->actingAs($this->userWithRole($role));

            $this->assertTrue(SolicitudCargaCombustibleResource::canEdit(new CargaCombustible));
            $this->assertTrue(SolicitudCargaCombustibleResource::canUpdateSolicitud());
        }
    }

    public function test_chofer_no_recibe_autorizacion_adicional_para_editar_solicitudes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->actingAs($this->userWithRole('chofer'));

        $this->assertFalse(SolicitudCargaCombustibleResource::canEdit(new CargaCombustible));
        $this->assertFalse(SolicitudCargaCombustibleResource::canUpdateSolicitud());
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
