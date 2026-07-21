<?php

namespace Tests\Feature;

use App\Filament\Resources\CargaCombustibleResource\Pages\CreateCargaCombustible;
use App\Models\TarjetaCombustible;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoTarjeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CargaCombustibleCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chofer_estricto_sobrescribe_fecha_carga_en_creacion_normal(): void
    {
        $this->crearRolesBase();
        Carbon::setTestNow('2026-07-20 14:30:00');

        $chofer = User::factory()->create();
        $chofer->assignRole('chofer');

        [$vehiculo, $tarjeta] = $this->crearVehiculoConTarjetaYResponsable();

        $this->actingAs($chofer);

        $page = app(CreateCargaCombustible::class);
        $method = new ReflectionMethod($page, 'mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $data = $method->invoke($page, [
            'vehiculo_id' => $vehiculo->id,
            'fecha_carga' => '2026-07-19 08:00:00',
            'km_odometro' => 12345,
            'litros' => 40.000,
            'importe' => 1200.00,
            'precio_litro' => 30.00,
        ]);

        $this->assertSame('2026-07-20 14:30:00', $data['fecha_carga']);
        $this->assertSame($tarjeta->id, $data['tarjeta_combustible_id']);

        Carbon::setTestNow();
    }

    public function test_usuario_con_chofer_y_responsable_no_se_trata_como_chofer_estricto(): void
    {
        $this->crearRolesBase();
        Carbon::setTestNow('2026-07-20 14:30:00');

        $usuario = User::factory()->create();
        $usuario->assignRole(['chofer', 'responsable']);

        [$vehiculo] = $this->crearVehiculoConTarjetaYResponsable();

        $this->actingAs($usuario);

        $page = app(CreateCargaCombustible::class);
        $method = new ReflectionMethod($page, 'mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $data = $method->invoke($page, [
            'vehiculo_id' => $vehiculo->id,
            'fecha_carga' => '2026-07-19 08:00:00',
            'km_odometro' => 12345,
            'litros' => 40.000,
            'importe' => 1200.00,
            'precio_litro' => 30.00,
        ]);

        $this->assertSame('2026-07-19 08:00:00', $data['fecha_carga']);

        Carbon::setTestNow();
    }

    private function crearRolesBase(): void
    {
        foreach (['admin', 'responsable', 'activos', 'chofer'] as $rol) {
            Role::firstOrCreate([
                'name' => $rol,
                'guard_name' => 'web',
            ]);
        }
    }

    private function crearVehiculoConTarjetaYResponsable(): array
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

        $vehiculo = Vehiculo::create([
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'estatus_id' => $estatusId,
            'placas' => 'TST1234',
            'marca' => 'Nissan',
            'modelo' => 'NP300',
            'rendimiento_optimo_km_l' => 10.50,
            'tolerancia_pct' => 10.00,
            'activo' => true,
        ]);

        $tarjeta = TarjetaCombustible::create([
            'numero' => '5063542400257899',
            'descripcion' => 'Tarjeta prueba',
            'activo' => true,
        ]);

        VehiculoTarjeta::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta->id,
            'fecha_inicio' => now()->subDays(7)->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $responsable = User::factory()->create();

        VehiculoResponsable::create([
            'vehiculo_id' => $vehiculo->id,
            'responsable_user_id' => $responsable->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        return [$vehiculo, $tarjeta];
    }
}
