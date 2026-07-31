<?php

namespace Tests\Feature;

use App\Exports\ReporteCombustibleCopiaExport;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\ReporteCombustibleCopiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteCombustibleCopiaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_rendimiento_con_litros_de_la_carga_actual_y_exporta_el_mismo_resumen(): void
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
            'placas' => 'COPIA001',
            'marca' => 'Nissan',
            'modelo' => 'Versa',
            'rendimiento_optimo_km_l' => 10,
            'tolerancia_pct' => 10,
            'activo' => true,
        ]);
        $tarjetaId = DB::table('tarjeta_combustibles')->insertGetId([
            'numero' => '123456',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vehiculo_tarjetas')->insert([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjetaId,
            'fecha_inicio' => '2026-01-01',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['fecha_carga' => '2026-07-01 08:00:00', 'km_odometro' => 10000, 'litros' => 50],
            ['fecha_carga' => '2026-07-05 08:00:00', 'km_odometro' => 10350, 'litros' => 45],
            ['fecha_carga' => '2026-07-10 08:00:00', 'km_odometro' => 10665, 'litros' => 48],
        ] as $carga) {
            DB::table('carga_combustibles')->insert(array_merge($carga, [
                'vehiculo_id' => $vehiculo->id,
                'user_id' => $user->id,
                'importe' => 1000,
                'foto_odometro_path' => 'tests/odometro.jpg',
                'foto_ticket_path' => 'tests/ticket.jpg',
                'precio_litro' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $filters = [
            'vehiculo' => null,
            'inicio' => '2026-07-01',
            'fin' => '2026-07-10',
        ];
        $service = app(ReporteCombustibleCopiaService::class);
        $cargas = $service->cargasConRendimientoReal($filters)->sortBy('fecha_carga')->values();
        $resumen = $service->resumenVehiculos($filters)->first();

        $this->assertNull($cargas[0]->rendimiento_real_reporte);
        $this->assertSame('7.78', number_format($cargas[1]->rendimiento_real_reporte, 2, '.', ''));
        $this->assertSame('6.56', number_format($cargas[2]->rendimiento_real_reporte, 2, '.', ''));
        $this->assertSame(665.0, $resumen->km_recorridos);
        $this->assertSame(93.0, $resumen->litros_cargados);
        $this->assertSame('7.15', number_format($resumen->rendimiento_real, 2, '.', ''));
        $this->assertSame('10000', (string) $resumen->odometro_inicial);
        $this->assertSame('10665', (string) $resumen->odometro_final);
        $this->assertSame('123456', (string) $resumen->tarjeta);

        $exportRows = (new ReporteCombustibleCopiaExport($filters))->collection();
        $this->assertCount(2, $exportRows);
        $this->assertSame(93.0, $exportRows->last()[12]);
        $this->assertSame(7.15, round($exportRows->last()[13], 2));

        $detailFilters = $filters;
        $detailFilters['vehiculo'] = $vehiculo->id;
        $detailExport = new ReporteCombustibleCopiaExport($detailFilters);
        $this->assertCount(4, $detailExport->collection());
        $this->assertSame('Odometro Anterior', $detailExport->headings()[11]);
    }
}
