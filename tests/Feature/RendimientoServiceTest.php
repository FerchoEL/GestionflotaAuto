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
use Tests\TestCase;

class RendimientoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_rendimiento_con_litros_de_la_carga_anterior(): void
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
            'placas' => 'RMY682B',
            'marca' => 'Hyundai',
            'modelo' => 'Sonata',
            'rendimiento_optimo_km_l' => 9.00,
            'tolerancia_pct' => 10.00,
            'activo' => true,
        ]);

        $cargaBaseId = DB::table('carga_combustibles')->insertGetId([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => null,
            'user_id' => $user->id,
            'fecha_carga' => '2026-05-15 21:47:25',
            'km_odometro' => 100,
            'litros' => 20.00,
            'importe' => 598.00,
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
            'km_odometro' => 276,
            'litros' => 60.00,
            'importe' => 1770.00,
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

        $this->assertSame(100, $rendimiento->km_anterior);
        $this->assertSame(176, $rendimiento->km_recorridos);
        $this->assertSame('8.80', number_format((float) $rendimiento->rendimiento_km_l, 2, '.', ''));
        $this->assertDatabaseCount('alerta_rendimientos', 0);
        $this->assertNull(AlertaRendimiento::first());
    }
}
