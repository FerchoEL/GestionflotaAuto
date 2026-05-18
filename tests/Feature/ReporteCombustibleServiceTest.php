<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehiculo;
use App\Services\ReporteCombustibleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteCombustibleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_historial_vehiculo_usa_litros_de_la_carga_anterior_para_el_rendimiento(): void
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

        DB::table('carga_combustibles')->insert([
            [
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
            ],
            [
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
            ],
            [
                'vehiculo_id' => $vehiculo->id,
                'tarjeta_combustible_id' => null,
                'user_id' => $user->id,
                'fecha_carga' => '2026-05-15 23:01:47',
                'km_odometro' => 816,
                'litros' => 60.00,
                'importe' => 1668.00,
                'foto_odometro_path' => 'tests/odometro-tercera.jpg',
                'foto_ticket_path' => 'tests/ticket-tercera.jpg',
                'precio_litro' => 27.80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $historial = app(ReporteCombustibleService::class)
            ->historialVehiculoConRendimientoReal($vehiculo->id, 10, 'historialPage');

        $rows = $historial->items();

        $this->assertCount(3, $rows);
        $this->assertSame('9.00', number_format((float) $rows[0]->rendimiento_real_reporte, 2, '.', ''));
        $this->assertSame('8.80', number_format((float) $rows[1]->rendimiento_real_reporte, 2, '.', ''));
        $this->assertNull($rows[2]->rendimiento_real_reporte);
        $this->assertSame('20.00', number_format((float) $rows[1]->litros_consumo_reporte, 2, '.', ''));
        $this->assertSame('60.00', number_format((float) $rows[0]->litros_consumo_reporte, 2, '.', ''));
    }
}
