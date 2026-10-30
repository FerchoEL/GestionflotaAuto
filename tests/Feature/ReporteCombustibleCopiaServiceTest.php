<?php

namespace Tests\Feature;

use App\Exports\ReporteCombustibleCopiaExport;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\ReporteCombustibleCopiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            'numero' => '5063542400257899',
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
        $this->assertSame('5063542400257899', (string) $resumen->tarjeta);

        $exportRows = (new ReporteCombustibleCopiaExport($filters))->collection();
        $this->assertCount(2, $exportRows);
        $this->assertSame(93.0, $exportRows->last()[12]);
        $this->assertSame(7.15, round($exportRows->last()[13], 2));

        $detailFilters = $filters;
        $detailFilters['vehiculo'] = $vehiculo->id;
        $detailExport = new ReporteCombustibleCopiaExport($detailFilters);
        $this->assertCount(4, $detailExport->collection());
        $this->assertSame('Odometro Anterior', $detailExport->headings()[11]);

        $this->assertExportedCardIsTextAndNumericColumnsRemainNumeric($filters, 'I');
        $this->assertExportedCardIsTextAndNumericColumnsRemainNumeric($detailFilters, 'J');

        $sinCapturas = $vehiculo->replicate();
        $sinCapturas->placas = 'SIN-CARGAS';
        $sinCapturas->tipo_combustible = 'diesel';
        $sinCapturas->save();
        $inactivo = $vehiculo->replicate();
        $inactivo->placas = 'INACTIVO';
        $inactivo->activo = false;
        $inactivo->save();

        $flotaFilters = array_merge($filters, ['flota_total' => true]);
        $flota = $service->datosReporte($flotaFilters);
        $this->assertCount(3, $flota);
        $this->assertEquals($resumen, $flota->first());
        $sinRegistro = $flota->firstWhere('vehiculo_id', $sinCapturas->id);
        $this->assertSame(0, $sinRegistro->litros_cargados);
        $this->assertSame(0, $sinRegistro->km_recorridos);
        $this->assertSame(0, $sinRegistro->importe);
        $this->assertNull($sinRegistro->rendimiento_real);
        $this->assertNull($sinRegistro->odometro_inicial);
        $this->assertNull($sinRegistro->odometro_final);
        $this->assertCount(1, $service->datosReporte($filters));

        $flotaExport = new ReporteCombustibleCopiaExport($flotaFilters);
        $this->assertSame((new ReporteCombustibleCopiaExport($filters))->headings(), $flotaExport->headings());
        $this->assertCount(4, $flotaExport->collection());
        $this->assertSame($exportRows->last(), $flotaExport->collection()->last());
        $this->assertExportedCardIsTextAndNumericColumnsRemainNumeric($flotaFilters, 'I');

        $seleccion = new ReporteCombustibleCopiaExport(array_merge($flotaFilters, ['vehiculo' => $sinCapturas->id]));
        $this->assertCount(16, $seleccion->headings());
        $this->assertCount(2, $seleccion->collection());
        $this->assertSame('SIN-CARGAS', $seleccion->collection()->first()[1]);
        $path = tempnam(sys_get_temp_dir(), 'flota-total-');
        file_put_contents($path, Excel::raw($seleccion, ExcelWriter::XLSX));
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('P', $sheet->getHighestColumn());
            foreach (['L2', 'M2', 'P2'] as $cell) {
                $this->assertSame(0, $sheet->getCell($cell)->getValue());
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($cell)->getDataType());
            }
            foreach (['J2', 'K2', 'N2'] as $cell) {
                $this->assertNull($sheet->getCell($cell)->getValue());
            }
        } finally {
            unlink($path);
        }

        $fueraPeriodo = $service->datosReporte(array_merge($flotaFilters, ['inicio' => '2026-08-01', 'fin' => '2026-08-31']));
        $this->assertCount(3, $fueraPeriodo);
        $this->assertSame(0, $fueraPeriodo->sum('importe'));
        $this->assertNull($fueraPeriodo->firstWhere('vehiculo_id', $vehiculo->id)->rendimiento_real);
        $this->assertSame([$sinCapturas->id], $service->datosReporte(array_merge($flotaFilters, ['tipo_combustible' => 'diesel']))->pluck('vehiculo_id')->all());
        foreach (['departamento', 'localidad', 'cuenta'] as $filtro) {
            $this->assertCount(0, $service->datosReporte(array_merge($flotaFilters, [$filtro => 999999])));
        }

        $cuentaId = DB::table('cuenta_analiticas')->insertGetId([
            'nombre' => 'Cuenta sin capturas', 'activo' => true,
        ]);
        DB::table('vehiculo_cuenta_analiticas')->insert([
            'vehiculo_id' => $sinCapturas->id, 'cuenta_analitica_id' => $cuentaId,
            'fecha_inicio' => '2026-01-01', 'activo' => true,
        ]);
        $this->assertSame([$sinCapturas->id], $service->datosReporte(array_merge($flotaFilters, ['cuenta' => $cuentaId]))->pluck('vehiculo_id')->all());

        // Una unidad sin cargas tampoco debe eludir el alcance del usuario.
        $this->actingAs($user);
        $this->assertCount(0, $service->datosReporte($flotaFilters));
        \Spatie\Permission\Models\Role::findOrCreate('responsable', 'web');
        $user->assignRole('responsable');
        DB::table('vehiculo_responsables')->insert([
            'vehiculo_id' => $sinCapturas->id, 'responsable_user_id' => $user->id,
            'fecha_inicio' => '2026-01-01', 'activo' => true,
        ]);
        $this->assertSame([$sinCapturas->id], $service->datosReporte($flotaFilters)->pluck('vehiculo_id')->all());
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        $user->assignRole('admin');
        $this->assertCount(3, $service->datosReporte($flotaFilters));
    }

    public function test_exportar_flota_total_requiere_permiso(): void
    {
        $this->actingAs(User::factory()->create());
        try {
            (new \App\Filament\Pages\ReporteCombustibleCopia)->exportarFlotaTotal();
            $this->fail('La exportación debe exigir permiso.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function assertExportedCardIsTextAndNumericColumnsRemainNumeric(array $filters, string $cardColumn): void
    {
        $path = tempnam(sys_get_temp_dir(), 'reporte-combustible-');
        file_put_contents($path, Excel::raw(new ReporteCombustibleCopiaExport($filters), ExcelWriter::XLSX));

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $this->assertSame('5063542400257899', $sheet->getCell($cardColumn . '2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($cardColumn . '2')->getDataType());

            $numericColumn = $filters['vehiculo'] ? 'O' : 'N';
            $this->assertIsNumeric($sheet->getCell($numericColumn . '2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($numericColumn . '2')->getDataType());
        } finally {
            unlink($path);
        }
    }
}
