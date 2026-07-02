<?php

namespace Tests\Feature;

use App\Models\CargaCombustible;
use App\Models\TarjetaCombustible;
use App\Models\TarjetaSaldoMovimiento;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoTarjeta;
use App\Services\TarjetaMovimientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CargaCombustibleImporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_importe_oficial_y_sincroniza_movimiento_con_el_monto_del_ticket(): void
    {
        $user = User::factory()->create();

        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Sedan',
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
            'placas' => 'TEST-123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'rendimiento_optimo_km_l' => 12.00,
            'tolerancia_pct' => 10.00,
            'activo' => true,
        ]);

        $tarjeta = TarjetaCombustible::create([
            'numero' => '1234567890',
            'descripcion' => 'Tarjeta de prueba',
            'activo' => true,
        ]);

        VehiculoTarjeta::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $this->app->instance(
            TarjetaMovimientoService::class,
            Mockery::mock(TarjetaMovimientoService::class)->makePartial()->shouldReceive(
                'resolverTarjetaIdVehiculoEnFecha'
            )->andReturn($tarjeta->id)->getMock()
        );

        $carga = CargaCombustible::create([
            'vehiculo_id' => $vehiculo->id,
            'user_id' => $user->id,
            'fecha_carga' => '2026-06-29 10:15:00',
            'km_odometro' => 12345,
            'litros' => 71.712,
            'precio_litro' => 27.89,
            'importe' => 2000.00,
            'foto_odometro_path' => 'tests/odometro.jpg',
            'foto_ticket_path' => 'tests/ticket.jpg',
            'foto_bomba_path' => null,
            'es_extemporanea' => false,
        ]);

        $carga->refresh();

        $this->assertSame('2000.00', number_format((float) $carga->importe, 2, '.', ''));
        $this->assertSame('27.8900', number_format((float) $carga->precio_litro, 4, '.', ''));
        $this->assertSame('71.712', number_format((float) $carga->litros, 3, '.', ''));
        $this->assertSame($tarjeta->id, (int) $carga->tarjeta_combustible_id);

        $movimiento = TarjetaSaldoMovimiento::query()
            ->where('origen_tipo', CargaCombustible::class)
            ->where('origen_id', $carga->id)
            ->firstOrFail();

        $this->assertSame('consumo_combustible', $movimiento->tipo);
        $this->assertSame('-2000.00', number_format((float) $movimiento->monto, 2, '.', ''));
    }

    public function test_permite_actualizar_importe_sin_re_subir_imagenes_y_mantiene_las_rutas(): void
    {
        $user = User::factory()->create();

        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Sedan',
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
            'placas' => 'TEST-456',
            'marca' => 'Nissan',
            'modelo' => 'Versa',
            'rendimiento_optimo_km_l' => 12.00,
            'tolerancia_pct' => 10.00,
            'activo' => true,
        ]);

        $tarjeta = TarjetaCombustible::create([
            'numero' => '9876543210',
            'descripcion' => 'Tarjeta de prueba 2',
            'activo' => true,
        ]);

        VehiculoTarjeta::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $this->app->instance(
            TarjetaMovimientoService::class,
            Mockery::mock(TarjetaMovimientoService::class)->makePartial()->shouldReceive(
                'resolverTarjetaIdVehiculoEnFecha'
            )->andReturn($tarjeta->id)->getMock()
        );

        $carga = CargaCombustible::create([
            'vehiculo_id' => $vehiculo->id,
            'user_id' => $user->id,
            'fecha_carga' => '2026-06-29 11:00:00',
            'km_odometro' => 22345,
            'litros' => 50.0000,
            'precio_litro' => 28.0000,
            'importe' => 1400.00,
            'foto_odometro_path' => 'tests/odometro-original.jpg',
            'foto_ticket_path' => 'tests/ticket-original.jpg',
            'foto_bomba_path' => 'tests/bomba-original.jpg',
            'es_extemporanea' => false,
        ]);

        $carga->update([
            'importe' => 1450.00,
            'precio_litro' => 29.0000,
            'litros' => 50.0000,
            'foto_odometro_path' => null,
            'foto_ticket_path' => null,
            'foto_bomba_path' => null,
        ]);

        $carga->refresh();

        $this->assertSame('tests/odometro-original.jpg', $carga->foto_odometro_path);
        $this->assertSame('tests/ticket-original.jpg', $carga->foto_ticket_path);
        $this->assertSame('tests/bomba-original.jpg', $carga->foto_bomba_path);
        $this->assertSame('1450.00', number_format((float) $carga->importe, 2, '.', ''));

        $movimiento = TarjetaSaldoMovimiento::query()
            ->where('origen_tipo', CargaCombustible::class)
            ->where('origen_id', $carga->id)
            ->firstOrFail();

        $this->assertSame('-1450.00', number_format((float) $movimiento->monto, 2, '.', ''));
    }
}
