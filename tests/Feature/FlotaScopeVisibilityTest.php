<?php

namespace Tests\Feature;

use App\Filament\Pages\ReporteCombustible;
use App\Filament\Pages\MisVehiculos;
use App\Filament\Pages\RegistrarCargaExtemporanea;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoChofer;
use App\Models\ResponsableAuxiliar;
use App\Models\VehiculoResponsable;
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

    public function test_responsable_ve_su_unidad_como_chofer_en_carga_extemporanea(): void
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

        app(VehiculoAsignacionActivaService::class)->guardarResponsable([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => User::factory()->create()->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $tarjetaId = DB::table('tarjeta_combustibles')->insertGetId([
            'numero' => 'TEST-122',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vehiculo_tarjetas')->insert([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjetaId,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($usuario);

        $opciones = app(RegistrarCargaExtemporanea::class)->vehiculosDisponibles();

        $this->assertArrayHasKey($vehiculo->id, $opciones);
    }

    public function test_auxiliar_activo_ve_los_vehiculos_del_responsable_apoyado(): void
    {
        $this->crearRolesBase();

        $responsable = User::factory()->create();
        $auxiliar = User::factory()->create();
        $vehiculo = $this->crearVehiculo('AUX-001', '201', 'VIN-AUX-001');

        $this->asignarResponsable($vehiculo, $responsable);
        $this->asignarAuxiliar($responsable, $auxiliar);
        $this->actingAs($auxiliar);

        $this->assertSame([$vehiculo->id], FlotaScope::idsVehiculosUsuario()->all());
    }

    public function test_dos_auxiliares_heredan_el_alcance_del_mismo_responsable(): void
    {
        $this->crearRolesBase();

        $responsable = User::factory()->create();
        $auxiliarA = User::factory()->create();
        $auxiliarB = User::factory()->create();
        $vehiculo = $this->crearVehiculo('AUX-008', '208', 'VIN-AUX-008');

        $this->asignarResponsable($vehiculo, $responsable);
        $this->asignarAuxiliar($responsable, $auxiliarA);
        $this->asignarAuxiliar($responsable, $auxiliarB);

        foreach ([$auxiliarA, $auxiliarB] as $auxiliar) {
            $this->actingAs($auxiliar);
            $this->assertSame([$vehiculo->id], FlotaScope::idsVehiculosUsuario()->all());
        }
    }

    public function test_el_alcance_del_auxiliar_se_actualiza_al_asignar_o_terminar_un_responsable(): void
    {
        $this->crearRolesBase();

        $responsable = User::factory()->create();
        $auxiliar = User::factory()->create();
        $vehiculoInicial = $this->crearVehiculo('AUX-009', '209', 'VIN-AUX-009');
        $vehiculoNuevo = $this->crearVehiculo('AUX-010', '210', 'VIN-AUX-010');

        $this->asignarResponsable($vehiculoInicial, $responsable);
        $this->asignarAuxiliar($responsable, $auxiliar);
        $this->actingAs($auxiliar);

        $this->assertContains($vehiculoInicial->id, FlotaScope::idsVehiculosUsuario()->all());

        $this->asignarResponsable($vehiculoNuevo, $responsable);
        $this->assertContains($vehiculoNuevo->id, FlotaScope::idsVehiculosUsuario()->all());

        /** @var VehiculoResponsable $asignacion */
        $asignacion = $vehiculoInicial->responsableActivo;
        $asignacion->update([
            'activo' => false,
            'fecha_fin' => now()->toDateString(),
        ]);

        $this->assertNotContains($vehiculoInicial->id, FlotaScope::idsVehiculosUsuario()->all());
        $this->assertContains($vehiculoNuevo->id, FlotaScope::idsVehiculosUsuario()->all());
    }

    public function test_auxiliar_obtiene_la_union_de_dos_responsables_y_no_ve_unidades_ajenas(): void
    {
        $this->crearRolesBase();

        $responsableA = User::factory()->create();
        $responsableB = User::factory()->create();
        $responsableAjeno = User::factory()->create();
        $auxiliar = User::factory()->create();
        $vehiculoA = $this->crearVehiculo('AUX-002', '202', 'VIN-AUX-002');
        $vehiculoB = $this->crearVehiculo('AUX-003', '203', 'VIN-AUX-003');
        $vehiculoAjeno = $this->crearVehiculo('AUX-004', '204', 'VIN-AUX-004');

        $this->asignarResponsable($vehiculoA, $responsableA);
        $this->asignarResponsable($vehiculoB, $responsableB);
        $this->asignarResponsable($vehiculoAjeno, $responsableAjeno);
        $this->asignarAuxiliar($responsableA, $auxiliar);
        $this->asignarAuxiliar($responsableB, $auxiliar);
        $this->actingAs($auxiliar);

        $this->assertEqualsCanonicalizing(
            [$vehiculoA->id, $vehiculoB->id],
            FlotaScope::idsVehiculosUsuario()->all()
        );
    }

    public function test_relacion_inactiva_no_otorga_acceso_y_el_acceso_por_otra_via_se_conserva(): void
    {
        $this->crearRolesBase();

        $responsable = User::factory()->create();
        $auxiliar = User::factory()->create();
        $auxiliar->assignRole('chofer');
        $vehiculoHeredado = $this->crearVehiculo('AUX-005', '205', 'VIN-AUX-005');
        $vehiculoComoChofer = $this->crearVehiculo('AUX-006', '206', 'VIN-AUX-006');

        $this->asignarResponsable($vehiculoHeredado, $responsable);
        $this->asignarAuxiliar($responsable, $auxiliar, false);
        VehiculoChofer::create([
            'vehiculo_id' => $vehiculoComoChofer->id,
            'chofer_user_id' => $auxiliar->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);
        $this->actingAs($auxiliar);

        $this->assertSame([$vehiculoComoChofer->id], FlotaScope::idsVehiculosUsuario()->all());
    }

    public function test_mis_vehiculos_rechaza_un_id_fuera_del_alcance(): void
    {
        $this->crearRolesBase();

        $auxiliar = User::factory()->create();
        $vehiculo = $this->crearVehiculo('AUX-007', '207', 'VIN-AUX-007');
        $this->actingAs($auxiliar);

        $pagina = app(MisVehiculos::class);
        $pagina->vehiculoId = $vehiculo->id;

        $this->assertNull($pagina->vehiculoSeleccionado());
    }

    private function asignarResponsable(Vehiculo $vehiculo, User $responsable): void
    {
        app(VehiculoAsignacionActivaService::class)->guardarResponsable([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => $responsable->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);
    }

    private function asignarAuxiliar(User $responsable, User $auxiliar, bool $activo = true): void
    {
        ResponsableAuxiliar::create([
            'responsable_user_id' => $responsable->id,
            'auxiliar_user_id' => $auxiliar->id,
            'activo' => $activo,
        ]);
    }

    private function crearRolesBase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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
