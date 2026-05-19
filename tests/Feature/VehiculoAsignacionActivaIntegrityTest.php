<?php

namespace Tests\Feature;

use App\Models\VehiculoDepartamento;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoTarjeta;
use App\Services\VehiculoAsignacionActivaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehiculoAsignacionActivaIntegrityTest extends TestCase
{
    use DatabaseMigrations;

    public function test_cierra_el_departamento_activo_anterior_del_vehiculo(): void
    {
        [$vehiculoId] = $this->crearVehiculoBase();
        $departamentoA = $this->crearDepartamento('Operaciones');
        $departamentoB = $this->crearDepartamento('Mantenimiento');

        $service = app(VehiculoAsignacionActivaService::class);

        $primero = $service->guardarDepartamento([
            'vehiculo_id' => $vehiculoId,
            'departamento_id' => $departamentoA,
            'fecha_inicio' => '2026-05-01',
            'activo' => true,
        ]);

        $segundo = $service->guardarDepartamento([
            'vehiculo_id' => $vehiculoId,
            'departamento_id' => $departamentoB,
            'fecha_inicio' => '2026-05-10',
            'activo' => true,
        ]);

        $this->assertFalse($primero->fresh()->activo);
        $this->assertSame('2026-05-10', $primero->fresh()->fecha_fin?->toDateString());
        $this->assertTrue($segundo->fresh()->activo);
        $this->assertDatabaseCount('vehiculo_departamentos', 2);
        $this->assertDatabaseCount('vehiculo_departamentos', 2);
        $this->assertSame(
            1,
            VehiculoDepartamento::query()->where('vehiculo_id', $vehiculoId)->where('activo', true)->count()
        );
    }

    public function test_cierra_el_responsable_activo_anterior_del_vehiculo(): void
    {
        [$vehiculoId] = $this->crearVehiculoBase();
        $this->crearRolesMinimos();
        $responsableA = $this->crearUsuario('resp-a@acme.com');
        $responsableB = $this->crearUsuario('resp-b@acme.com');

        $service = app(VehiculoAsignacionActivaService::class);

        $service->guardarResponsable([
            'vehiculo_id' => $vehiculoId,
            'responsable_user_id' => $responsableA,
            'fecha_inicio' => '2026-05-01',
            'activo' => true,
        ]);

        $service->guardarResponsable([
            'vehiculo_id' => $vehiculoId,
            'responsable_user_id' => $responsableB,
            'fecha_inicio' => '2026-05-10',
            'activo' => true,
        ]);

        $this->assertSame(
            1,
            VehiculoResponsable::query()->where('vehiculo_id', $vehiculoId)->where('activo', true)->count()
        );

        $this->assertSame(
            $responsableB,
            VehiculoResponsable::query()
                ->where('vehiculo_id', $vehiculoId)
                ->where('activo', true)
                ->value('responsable_user_id')
        );
    }

    public function test_cierra_la_tarjeta_activa_anterior_del_vehiculo_y_de_la_tarjeta(): void
    {
        [$vehiculoA] = $this->crearVehiculoBase('AAA1111');
        [$vehiculoB] = $this->crearVehiculoBase('BBB2222', 'CAMION-02', 'VINBBB2222');
        $tarjetaId = $this->crearTarjeta('1234567890');

        $service = app(VehiculoAsignacionActivaService::class);

        $primera = $service->guardarTarjeta([
            'vehiculo_id' => $vehiculoA,
            'tarjeta_combustible_id' => $tarjetaId,
            'fecha_inicio' => '2026-05-01',
            'activo' => true,
        ]);

        $segunda = $service->guardarTarjeta([
            'vehiculo_id' => $vehiculoB,
            'tarjeta_combustible_id' => $tarjetaId,
            'fecha_inicio' => '2026-05-10',
            'activo' => true,
        ]);

        $this->assertFalse($primera->fresh()->activo);
        $this->assertTrue($segunda->fresh()->activo);
        $this->assertSame(
            1,
            VehiculoTarjeta::query()->where('tarjeta_combustible_id', $tarjetaId)->where('activo', true)->count()
        );
        $this->assertSame(
            $vehiculoB,
            VehiculoTarjeta::query()->where('tarjeta_combustible_id', $tarjetaId)->where('activo', true)->value('vehiculo_id')
        );
    }

    public function test_la_base_impide_dos_departamentos_activos_para_el_mismo_vehiculo(): void
    {
        [$vehiculoId] = $this->crearVehiculoBase();
        $departamentoA = $this->crearDepartamento('Operaciones');
        $departamentoB = $this->crearDepartamento('Mantenimiento');

        DB::table('vehiculo_departamentos')->insert([
            'vehiculo_id' => $vehiculoId,
            'departamento_id' => $departamentoA,
            'asignado_por_user_id' => null,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('vehiculo_departamentos')->insert([
            'vehiculo_id' => $vehiculoId,
            'departamento_id' => $departamentoB,
            'asignado_por_user_id' => null,
            'fecha_inicio' => '2026-05-10',
            'fecha_fin' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearVehiculoBase(
        string $placas = 'ABC1234',
        string $numeroEconomico = 'CAMION-01',
        string $vin = 'VINABC12345678901'
    ): array {
        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Pickup ' . $placas,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estatusId = DB::table('vehiculo_estatus')->insertGetId([
            'nombre' => 'Activo ' . $placas,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehiculoId = DB::table('vehiculos')->insertGetId([
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'estatus_id' => $estatusId,
            'numero_economico' => $numeroEconomico,
            'placas' => $placas,
            'vin' => $vin,
            'marca' => 'Nissan',
            'modelo' => 'NP300',
            'rendimiento_optimo_km_l' => 10.00,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$vehiculoId, $tipoVehiculoId, $estatusId];
    }

    private function crearDepartamento(string $nombre): int
    {
        return DB::table('departamentos')->insertGetId([
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearTarjeta(string $numero): int
    {
        return DB::table('tarjeta_combustibles')->insertGetId([
            'numero' => $numero,
            'descripcion' => 'Tarjeta ' . $numero,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuario(string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearRolesMinimos(): void
    {
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
    }
}
