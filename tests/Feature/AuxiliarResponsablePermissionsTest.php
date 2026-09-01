<?php

namespace Tests\Feature;

use App\Filament\Pages\MisVehiculos;
use App\Filament\Pages\RegistrarCargaExtemporanea;
use App\Filament\Pages\ReporteCombustible;
use App\Filament\Pages\ReporteDocumentos;
use App\Filament\Resources\AlertaDocumentoResource;
use App\Filament\Resources\AlertaRendimientoResource;
use App\Filament\Resources\CargaCombustibleResource;
use App\Filament\Resources\VehiculoResponsableResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuxiliarResponsablePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auxiliar_recibe_los_permisos_operativos_y_no_los_administrativos(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $auxiliar = $this->userWithRole('auxiliar_responsable');

        foreach ([
            'pagina.mis-vehiculos.view',
            'carga-combustible.view',
            'carga-combustible.create',
            'vehiculo-documento.view',
            'vehiculo-documento.create',
            'alerta-documento.view',
            'alerta-rendimiento.view',
            'reporte-combustible.export',
            'reporte-documentos.export',
        ] as $permission) {
            $this->assertTrue($auxiliar->can($permission), $permission);
        }

        foreach ([
            'vehiculo-responsable.view',
            'usuario.view',
            'rol.view',
            'vehiculo.update',
        ] as $permission) {
            $this->assertFalse($auxiliar->can($permission), $permission);
        }
    }

    public function test_auxiliar_puede_acceder_a_las_paginas_operativas_y_no_a_administracion(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $this->actingAs($auxiliar);

        $this->assertTrue(MisVehiculos::canAccess());
        $this->assertTrue(RegistrarCargaExtemporanea::canAccess());
        $this->assertTrue(ReporteCombustible::canAccess());
        $this->assertTrue(ReporteDocumentos::canAccess());
        $this->assertTrue(CargaCombustibleResource::canViewAny());
        $this->assertTrue(AlertaDocumentoResource::canViewAny());
        $this->assertTrue(AlertaRendimientoResource::canViewAny());
        $this->assertFalse(VehiculoResponsableResource::canViewAny());
        $this->assertFalse(RoleResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_responsable_y_admin_conservan_sus_permisos_y_los_roles_se_acumulan(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $responsable = $this->userWithRole('responsable');
        $admin = $this->userWithRole('admin');
        $mixto = $this->userWithRole(['chofer', 'auxiliar_responsable']);

        $this->assertTrue($responsable->can('carga-combustible.update-own-assignment'));
        $this->assertTrue($responsable->can('reporte-documentos.export'));
        $this->assertTrue($admin->can('vehiculo-responsable.view'));
        $this->assertTrue($admin->can('rol.view'));
        $this->assertTrue($mixto->can('carga-combustible.create'));
        $this->assertTrue($mixto->can('pagina.mis-vehiculos.view'));
    }

    public function test_la_captura_conserva_el_responsable_formal_y_el_actor_real(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $responsable = $this->userWithRole('responsable');
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Prueba',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $estatusId = DB::table('vehiculo_estatus')->insertGetId([
            'nombre' => 'Activo',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vehiculoId = DB::table('vehiculos')->insertGetId([
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'estatus_id' => $estatusId,
            'placas' => 'PERM-001',
            'marca' => 'Prueba',
            'modelo' => 'Prueba',
            'rendimiento_optimo_km_l' => 10,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehiculo_responsables')->insert([
            'vehiculo_id' => $vehiculoId,
            'responsable_user_id' => $responsable->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaId = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoId,
            'user_id' => $auxiliar->id,
            'registrada_por_user_id' => $auxiliar->id,
            'fecha_carga' => now(),
            'km_odometro' => 100,
            'litros' => 10,
            'precio_litro' => 20,
            'foto_odometro_path' => 'test/odometro.jpg',
            'foto_ticket_path' => 'test/ticket.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($responsable->id, DB::table('vehiculo_responsables')->where('vehiculo_id', $vehiculoId)->value('responsable_user_id'));
        $this->assertSame($auxiliar->id, DB::table('carga_combustibles')->where('id', $cargaId)->value('registrada_por_user_id'));
    }

    private function userWithRole(string|array $roles): User
    {
        $user = User::factory()->create();
        $user->assignRole($roles);

        return $user;
    }
}
