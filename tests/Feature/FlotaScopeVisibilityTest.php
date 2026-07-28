<?php

namespace Tests\Feature;

use App\Filament\Pages\ReporteCombustible;
use App\Filament\Pages\MisVehiculos;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoChofer;
use App\Services\VehiculoAsignacionActivaService;
use App\Services\ReporteCombustibleService;
use App\Support\FlotaScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FlotaScopeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_con_chofer_y_responsable_ve_la_union_de_sus_vehiculos(): void
    {
        $this->crearRolesBase();

        $usuario = User::factory()->create([
            'email' => 'sergio.felix@kpgroup.mx',
            'name' => 'Sergio Felix',
        ]);
        $usuario->assignRole(['chofer', 'responsable']);

        $vehiculoComoChofer = $this->crearVehiculo('PN2369B', '122', '9BD281H68PYY57371');
        $vehiculoComoResponsable = $this->crearVehiculo('PRUEBA2', '123', '9BD281H68PYY57372');

        VehiculoChofer::create([
            'vehiculo_id' => $vehiculoComoChofer->id,
            'chofer_user_id' => $usuario->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        app(VehiculoAsignacionActivaService::class)->guardarResponsable([
            'vehiculo_id' => $vehiculoComoResponsable->id,
            'responsable_user_id' => $usuario->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $this->actingAs($usuario);

        $idsVisibles = FlotaScope::idsVehiculosUsuario()->all();

        $this->assertContains($vehiculoComoChofer->id, $idsVisibles);
        $this->assertContains($vehiculoComoResponsable->id, $idsVisibles);

        $vehiculosPagina = app(MisVehiculos::class)->vehiculosAsignados()->pluck('id')->all();

        $this->assertContains($vehiculoComoChofer->id, $vehiculosPagina);
        $this->assertContains($vehiculoComoResponsable->id, $vehiculosPagina);
    }

    public function test_un_responsable_tambien_ve_una_unidad_donde_esta_asignado_como_chofer(): void
    {
        $this->crearRolesBase();

        $usuario = User::factory()->create([
            'email' => 'sergio.felix@kpgroup.mx',
            'name' => 'Sergio Felix',
        ]);
        $usuario->assignRole('responsable');

        $vehiculo = $this->crearVehiculo('PN2369B', '122', '9BD281H68PYY57371');

        VehiculoChofer::create([
            'vehiculo_id' => $vehiculo->id,
            'chofer_user_id' => $usuario->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $this->actingAs($usuario);

        $this->assertContains($vehiculo->id, FlotaScope::idsVehiculosUsuario()->all());
        $this->assertContains($vehiculo->id, app(MisVehiculos::class)->vehiculosAsignados()->pluck('id')->all());
    }

    public function test_un_responsable_puede_entrar_al_reporte_de_combustible(): void
    {
        $this->crearRolesBase();

        $usuario = User::factory()->create([
            'email' => 'sergio.felix@kpgroup.mx',
            'name' => 'Sergio Felix',
        ]);
        $usuario->assignRole(['chofer', 'responsable']);

        $this->actingAs($usuario);

        $this->assertTrue(ReporteCombustible::canAccess());
    }

    public function test_reporte_de_combustible_usa_unidades_de_chofer_y_responsable(): void
    {
        $this->crearRolesBase();

        $usuario = User::factory()->create([
            'email' => 'sergio.felix@kpgroup.mx',
            'name' => 'Sergio Felix',
        ]);
        $usuario->assignRole('responsable');

        $vehiculoAsignado = $this->crearVehiculo('PN2369B', '122', '9BD281H68PYY57371');
        $vehiculoAjeno = $this->crearVehiculo('AJENA1', '999', '9BD281H68PYY99999');

        VehiculoChofer::create([
            'vehiculo_id' => $vehiculoAsignado->id,
            'chofer_user_id' => $usuario->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        foreach ([$vehiculoAsignado, $vehiculoAjeno] as $vehiculo) {
            DB::table('carga_combustibles')->insert([
                'vehiculo_id' => $vehiculo->id,
                'user_id' => $usuario->id,
                'fecha_carga' => now(),
                'km_odometro' => 100,
                'litros' => 10,
                'precio_litro' => 20,
                'importe' => 200,
                'foto_odometro_path' => 'test/odometro.jpg',
                'foto_ticket_path' => 'test/ticket.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($usuario);

        $cargas = app(ReporteCombustibleService::class)->cargasConRendimientoReal([
            'inicio' => now()->startOfDay()->toDateString(),
            'fin' => now()->toDateString(),
        ]);

        $this->assertSame([$vehiculoAsignado->id], $cargas->pluck('vehiculo_id')->all());
    }

    private function crearRolesBase(): void
    {
        foreach (['admin', 'responsable', 'activos', 'chofer', 'fondeo'] as $rol) {
            Role::firstOrCreate([
                'name' => $rol,
                'guard_name' => 'web',
            ]);
        }
    }

    private function crearVehiculo(string $placas, string $numeroEconomico, string $vin): Vehiculo
    {
        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Camioneta',
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

        return Vehiculo::create([
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'estatus_id' => $estatusId,
            'placas' => $placas,
            'numero_economico' => $numeroEconomico,
            'vin' => $vin,
            'marca' => 'CHRYSLER',
            'modelo' => 'RAM 700',
            'anio' => 2023,
            'color' => 'GRIS',
            'capacidad_tanque_litros' => 55,
            'rendimiento_optimo_km_l' => 14,
            'tolerancia_pct' => 10,
            'activo' => true,
        ]);
    }
}
