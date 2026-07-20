<?php

namespace Tests\Feature;

use App\Models\AlertaRendimiento;
use App\Models\CargaCombustible;
use App\Models\Rendimiento;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\RendimientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class RendimientoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_rendimiento_con_litros_de_la_carga_actual(): void
    {
        [$vehiculo, $user] = $this->crearVehiculoYUsuario();

        $cargaBaseId = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 21:47:25',
            'km_odometro' => 10000,
            'litros' => 50.00,
            'importe' => 1495.00,
            'foto_odometro_path' => 'tests/odometro-base.jpg',
            'foto_ticket_path' => 'tests/ticket-base.jpg',
            'precio_litro' => 29.90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaEvaluadaId = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 21:55:31',
            'km_odometro' => 10350,
            'litros' => 45.00,
            'importe' => 1332.00,
            'foto_odometro_path' => 'tests/odometro-evaluada.jpg',
            'foto_ticket_path' => 'tests/ticket-evaluada.jpg',
            'precio_litro' => 29.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(RendimientoService::class);

        $service->procesarCarga(CargaCombustible::findOrFail($cargaBaseId));
        $service->procesarCarga(CargaCombustible::findOrFail($cargaEvaluadaId));

        $rendimiento = Rendimiento::where('carga_id', $cargaEvaluadaId)->firstOrFail();

        $this->assertSame(10000, $rendimiento->km_anterior);
        $this->assertSame(350, $rendimiento->km_recorridos);
        $this->assertSame('7.78', number_format((float) $rendimiento->rendimiento_km_l, 2, '.', ''));
        $this->assertDatabaseCount('alerta_rendimientos', 0);
        $this->assertNull(AlertaRendimiento::first());
    }

    public function test_recalcula_desde_carga_usando_la_formula_nueva_para_las_posteriores(): void
    {
        [$vehiculo, $user] = $this->crearVehiculoYUsuario();

        $carga1Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 08:00:00',
            'km_odometro' => 10000,
            'litros' => 50.00,
            'importe' => 1495.00,
            'foto_odometro_path' => 'tests/odometro-1.jpg',
            'foto_ticket_path' => 'tests/ticket-1.jpg',
            'precio_litro' => 29.90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carga2Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 10:00:00',
            'km_odometro' => 10350,
            'litros' => 45.00,
            'importe' => 1332.00,
            'foto_odometro_path' => 'tests/odometro-2.jpg',
            'foto_ticket_path' => 'tests/ticket-2.jpg',
            'precio_litro' => 29.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carga3Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 12:00:00',
            'km_odometro' => 10665,
            'litros' => 48.00,
            'importe' => 1416.00,
            'foto_odometro_path' => 'tests/odometro-3.jpg',
            'foto_ticket_path' => 'tests/ticket-3.jpg',
            'precio_litro' => 29.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(RendimientoService::class);

        $service->procesarCarga(CargaCombustible::findOrFail($carga1Id));
        $service->procesarCarga(CargaCombustible::findOrFail($carga2Id));
        $service->procesarCarga(CargaCombustible::findOrFail($carga3Id));

        $rendimiento2 = Rendimiento::where('carga_id', $carga2Id)->firstOrFail();
        $rendimiento3 = Rendimiento::where('carga_id', $carga3Id)->firstOrFail();

        $this->assertSame('7.78', number_format((float) $rendimiento2->rendimiento_km_l, 2, '.', ''));
        $this->assertSame('6.56', number_format((float) $rendimiento3->rendimiento_km_l, 2, '.', ''));

        DB::table('carga_combustibles')
            ->where('id', $carga2Id)
            ->update([
                'fecha_carga' => '2026-05-15 09:00:00',
                'km_odometro' => 10380,
                'litros' => 40.00,
                'updated_at' => now(),
            ]);

        $service->recalcularDesdeCarga(CargaCombustible::findOrFail($carga2Id));

        $rendimiento2Recalculado = Rendimiento::where('carga_id', $carga2Id)->firstOrFail();
        $rendimiento3Recalculado = Rendimiento::where('carga_id', $carga3Id)->firstOrFail();

        $this->assertSame(380, $rendimiento2Recalculado->km_recorridos);
        $this->assertSame('9.50', number_format((float) $rendimiento2Recalculado->rendimiento_km_l, 2, '.', ''));
        $this->assertSame(285, $rendimiento3Recalculado->km_recorridos);
        $this->assertSame('5.94', number_format((float) $rendimiento3Recalculado->rendimiento_km_l, 2, '.', ''));
    }

    public function test_after_save_recalcula_el_vehiculo_actual_y_el_anterior_cuando_cambia_vehiculo(): void
    {
        [$vehiculoA, $user] = $this->crearVehiculoYUsuario('RMY682B');
        [$vehiculoB] = $this->crearVehiculoYUsuario('RMY683B');

        $vehiculoA->update([
            'rendimiento_optimo_km_l' => 100.00,
            'tolerancia_pct' => 100.00,
        ]);

        $vehiculoB->update([
            'rendimiento_optimo_km_l' => 100.00,
            'tolerancia_pct' => 100.00,
        ]);

        $cargaA1Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoA->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 08:00:00',
            'km_odometro' => 10000,
            'litros' => 50.00,
            'importe' => 1495.00,
            'foto_odometro_path' => 'tests/a1.jpg',
            'foto_ticket_path' => 'tests/a1-ticket.jpg',
            'precio_litro' => 29.90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaA2Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoA->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 10:00:00',
            'km_odometro' => 10350,
            'litros' => 45.00,
            'importe' => 1332.00,
            'foto_odometro_path' => 'tests/a2.jpg',
            'foto_ticket_path' => 'tests/a2-ticket.jpg',
            'precio_litro' => 29.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaA3Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoA->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 12:00:00',
            'km_odometro' => 10665,
            'litros' => 48.00,
            'importe' => 1416.00,
            'foto_odometro_path' => 'tests/a3.jpg',
            'foto_ticket_path' => 'tests/a3-ticket.jpg',
            'precio_litro' => 29.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaB1Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoB->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 09:00:00',
            'km_odometro' => 20000,
            'litros' => 60.00,
            'importe' => 1788.00,
            'foto_odometro_path' => 'tests/b1.jpg',
            'foto_ticket_path' => 'tests/b1-ticket.jpg',
            'precio_litro' => 29.80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cargaB2Id = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculoB->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 13:00:00',
            'km_odometro' => 20680,
            'litros' => 50.00,
            'importe' => 1490.00,
            'foto_odometro_path' => 'tests/b2.jpg',
            'foto_ticket_path' => 'tests/b2-ticket.jpg',
            'precio_litro' => 29.80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(RendimientoService::class);

        $service->procesarCarga(CargaCombustible::findOrFail($cargaA1Id));
        $service->procesarCarga(CargaCombustible::findOrFail($cargaA2Id));
        $service->procesarCarga(CargaCombustible::findOrFail($cargaA3Id));
        $service->procesarCarga(CargaCombustible::findOrFail($cargaB1Id));
        $service->procesarCarga(CargaCombustible::findOrFail($cargaB2Id));

        DB::table('carga_combustibles')
            ->where('id', $cargaA2Id)
            ->update([
                'vehiculo_id' => $vehiculoB->id,
                'fecha_carga' => '2026-05-15 11:00:00',
                'km_odometro' => 20280,
                'litros' => 40.00,
                'updated_at' => now(),
            ]);

        $page = app(\App\Filament\Resources\CargaCombustibleResource\Pages\EditCargaCombustible::class);

        (new ReflectionProperty($page, 'record'))->setValue($page, CargaCombustible::findOrFail($cargaA2Id));
        (new ReflectionProperty($page, 'vehiculoOriginalId'))->setValue($page, $vehiculoA->id);
        (new ReflectionProperty($page, 'fechaCargaOriginal'))->setValue($page, '2026-05-15 10:00:00');

        $method = new ReflectionMethod($page, 'afterSave');
        $method->setAccessible(true);
        $method->invoke($page);

        $rendimientoA3 = Rendimiento::where('carga_id', $cargaA3Id)->firstOrFail();
        $rendimientoA2Movida = Rendimiento::where('carga_id', $cargaA2Id)->firstOrFail();
        $rendimientoB2 = Rendimiento::where('carga_id', $cargaB2Id)->firstOrFail();

        $this->assertSame($vehiculoA->id, $rendimientoA3->vehiculo_id);
        $this->assertSame(665, $rendimientoA3->km_recorridos);
        $this->assertSame('13.85', number_format((float) $rendimientoA3->rendimiento_km_l, 2, '.', ''));

        $this->assertSame($vehiculoB->id, $rendimientoA2Movida->vehiculo_id);
        $this->assertSame(280, $rendimientoA2Movida->km_recorridos);
        $this->assertSame('7.00', number_format((float) $rendimientoA2Movida->rendimiento_km_l, 2, '.', ''));

        $this->assertSame($vehiculoB->id, $rendimientoB2->vehiculo_id);
        $this->assertSame(400, $rendimientoB2->km_recorridos);
        $this->assertSame('8.00', number_format((float) $rendimientoB2->rendimiento_km_l, 2, '.', ''));
    }

    private function crearVehiculoYUsuario(string $placas = 'RMY682B'): array
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
            'placas' => $placas,
            'marca' => 'Hyundai',
            'modelo' => 'Sonata',
            'rendimiento_optimo_km_l' => 9.00,
            'tolerancia_pct' => 50.00,
            'activo' => true,
        ]);

        return [$vehiculo, $user];
    }
}
