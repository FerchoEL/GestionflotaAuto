<?php

namespace Tests\Feature;

use App\Filament\Pages\RegistrarCargaExtemporanea;
use App\Models\CargaCombustible;
use App\Models\CuentaAnalitica;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoCuentaAnalitica;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoTarjeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CargaCuentaAnaliticaFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_captura_extemporanea_real_usa_la_cuenta_predeterminada(): void
    {
        Storage::fake('public');
        $responsable = $this->userWithRole('responsable');
        $cuenta = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $vehiculo = $this->vehicleWithAssignments($responsable, $cuenta);

        $this->actingAs($responsable);

        $page = app(RegistrarCargaExtemporanea::class);
        $page->data = $this->extemporaneaData($vehiculo);
        $page->save();

        $this->assertDatabaseHas('carga_combustibles', [
            'vehiculo_id' => $vehiculo->id,
            'es_extemporanea' => true,
            'cuenta_analitica_id' => $cuenta->id,
        ]);
    }

    public function test_captura_extemporanea_real_conserva_la_seleccion_manual(): void
    {
        Storage::fake('public');
        $responsable = $this->userWithRole('responsable');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $seleccionada = CuentaAnalitica::create(['nombre' => 'Cuenta B', 'activo' => true]);
        $vehiculo = $this->vehicleWithAssignments($responsable, $predeterminada);

        $this->actingAs($responsable);

        $page = app(RegistrarCargaExtemporanea::class);
        $data = $this->extemporaneaData($vehiculo);
        $data['cuenta_analitica_id'] = $seleccionada->id;
        $page->data = $data;
        $page->save();

        $this->assertDatabaseHas('carga_combustibles', [
            'vehiculo_id' => $vehiculo->id,
            'es_extemporanea' => true,
            'cuenta_analitica_id' => $seleccionada->id,
        ]);
    }

    public function test_cuenta_analitica_de_la_carga_es_un_snapshot_historico(): void
    {
        $responsable = $this->userWithRole('responsable');
        $cuentaA = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $cuentaB = CuentaAnalitica::create(['nombre' => 'Cuenta B', 'activo' => true]);
        $vehiculo = $this->vehicleWithAssignments($responsable, $cuentaA);

        $carga1 = $this->createCarga($vehiculo, $responsable, $cuentaA);

        VehiculoCuentaAnalitica::create([
            'vehiculo_id' => $vehiculo->id,
            'cuenta_analitica_id' => $cuentaB->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'activo' => true,
        ]);

        $carga2 = $this->createCarga($vehiculo, $responsable, $cuentaB);

        $this->assertSame($cuentaA->id, $carga1->fresh()->cuenta_analitica_id);
        $this->assertSame($cuentaB->id, $carga2->fresh()->cuenta_analitica_id);
    }

    private function userWithRole(string $role): User
    {
        $role = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function vehicleWithAssignments(User $responsable, CuentaAnalitica $cuenta): Vehiculo
    {
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

        $vehiculo = Vehiculo::create([
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'estatus_id' => $estatusId,
            'placas' => 'EXT-' . random_int(1000, 9999),
            'marca' => 'Prueba',
            'modelo' => 'Prueba',
            'rendimiento_optimo_km_l' => 10,
            'activo' => true,
        ]);

        VehiculoResponsable::create([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => $responsable->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $tarjeta = DB::table('tarjeta_combustibles')->insertGetId([
            'numero' => 'TAR-' . random_int(1000, 9999),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        VehiculoTarjeta::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta,
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        VehiculoCuentaAnalitica::create([
            'vehiculo_id' => $vehiculo->id,
            'cuenta_analitica_id' => $cuenta->id,
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);

        return $vehiculo->fresh();
    }

    private function extemporaneaData(Vehiculo $vehiculo): array
    {
        return [
            'vehiculo_id' => $vehiculo->id,
            'fecha_carga' => now()->format('Y-m-d H:i:s'),
            'km_odometro' => 100,
            'litros' => 10,
            'precio_litro' => 20,
            'importe' => 200,
            'es_extemporanea' => true,
            'motivo_correccion' => 'Prueba de cobertura',
            'foto_odometro_path' => [UploadedFile::fake()->image('odometro.jpg')],
            'foto_ticket_path' => [UploadedFile::fake()->image('ticket.jpg')],
            'foto_bomba_path' => [UploadedFile::fake()->image('bomba.jpg')],
        ];
    }

    private function createCarga(Vehiculo $vehiculo, User $usuario, CuentaAnalitica $cuenta): CargaCombustible
    {
        return CargaCombustible::create([
            'vehiculo_id' => $vehiculo->id,
            'user_id' => $usuario->id,
            'fecha_carga' => now(),
            'km_odometro' => random_int(100, 999),
            'litros' => 10,
            'precio_litro' => 20,
            'importe' => 200,
            'cuenta_analitica_id' => $cuenta->id,
            'foto_odometro_path' => 'tests/odometro.jpg',
            'foto_ticket_path' => 'tests/ticket.jpg',
            'foto_bomba_path' => 'tests/bomba.jpg',
        ]);
    }
}
