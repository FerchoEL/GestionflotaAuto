<?php

namespace Tests\Feature;

use App\Exports\CatalogoTarjetasCombustibleSheet;
use App\Models\TarjetaCombustible;
use App\Models\Vehiculo;
use App\Models\VehiculoTarjeta;
use App\Reports\CatalogoTarjetasCombustibleReport;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use ReflectionMethod;
use Tests\TestCase;

class CatalogoTarjetasCombustibleReportTest extends TestCase
{
    use DatabaseMigrations;

    public function test_incluye_tarjetas_asignadas_sin_asignar_y_omite_historicos(): void
    {
        $vehiculo = $this->crearVehiculo('UNIDAD-01', 'ABC1234');
        $asignada = $this->crearTarjeta('001234567890');
        $sinAsignar = $this->crearTarjeta('009876543210');
        $historica = $this->crearTarjeta('001111111111');

        $this->asignar($vehiculo, $asignada, true, null);
        $this->asignar($vehiculo, $historica, false, '2026-01-01');

        $datos = app(CatalogoTarjetasCombustibleReport::class)->build();

        $this->assertSame(3, $datos['resumen']['total']);
        $this->assertSame(1, $datos['resumen']['asignadas']);
        $this->assertSame(2, $datos['resumen']['sin_asignar']);

        $filaAsignada = $datos['todas']->firstWhere('id_tarjeta', $asignada);
        $filaSinAsignar = $datos['todas']->firstWhere('id_tarjeta', $sinAsignar);
        $filaHistorica = $datos['todas']->firstWhere('id_tarjeta', $historica);

        $this->assertSame('001234567890', $filaAsignada['numero_tarjeta']);
        $this->assertSame('Asignada', $filaAsignada['indicador_asignacion']);
        $this->assertSame('UNIDAD-01', $filaAsignada['numero_unidad']);
        $this->assertSame('Sin asignar', $filaSinAsignar['indicador_asignacion']);
        $this->assertSame('SIN ASIGNAR', $filaHistorica['clasificacion']);

        $sheet = new CatalogoTarjetasCombustibleSheet('Prueba', [$filaAsignada]);
        $this->assertSame(NumberFormat::FORMAT_TEXT, $sheet->columnFormats()['B']);
        $this->assertSame('001234567890', $sheet->array()[0][1]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $celda = $spreadsheet->getActiveSheet()->getCell('B2');
        $sheet->bindValue($celda, '001234567890');
        $this->assertSame('s', $celda->getDataType());
        $this->assertSame('001234567890', $celda->getValue());
    }

    public function test_marca_duplicidades_de_tarjeta_y_de_unidad(): void
    {
        $reporte = app(CatalogoTarjetasCombustibleReport::class);
        $tarjetaA = new TarjetaCombustible(['numero' => '0001', 'activo' => true]);
        $tarjetaB = new TarjetaCombustible(['numero' => '0002', 'activo' => true]);
        $tarjetaA->id = 1;
        $tarjetaB->id = 2;
        $vehiculoA = new Vehiculo(['numero_economico' => 'UNIDAD-A']);
        $vehiculoB = new Vehiculo(['numero_economico' => 'UNIDAD-B']);
        $vehiculoA->id = 10;
        $vehiculoB->id = 11;

        $asignacionA1 = $this->asignacionEnMemoria($tarjetaA, $vehiculoA, 1);
        $asignacionA2 = $this->asignacionEnMemoria($tarjetaA, $vehiculoB, 2);
        $asignacionB = $this->asignacionEnMemoria($tarjetaB, $vehiculoA, 3);
        $porUnidad = collect([10 => collect([$asignacionA1, $asignacionB]), 11 => collect([$asignacionA2])]);

        $metodo = new ReflectionMethod($reporte, 'fila');
        $fila = $metodo->invoke($reporte, $tarjetaA, collect([$asignacionA1, $asignacionA2]), $porUnidad);

        $this->assertSame('POSIBLE DUPLICIDAD', $fila['clasificacion']);
        $this->assertStringContainsString('tarjeta con 2 asignaciones vigentes', $fila['incidencias']);
        $this->assertStringContainsString('UNIDAD-A con varias tarjetas vigentes', $fila['incidencias']);
        $this->assertSame('UNIDAD-A | UNIDAD-B', $fila['unidades_involucradas']);
    }

    private function crearVehiculo(string $numero, string $placas): int
    {
        $tipo = DB::table('tipo_vehiculos')->insertGetId(['nombre' => 'Pickup '.$placas, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        $estatus = DB::table('vehiculo_estatus')->insertGetId(['nombre' => 'Activo', 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('vehiculos')->insertGetId([
            'tipo_vehiculo_id' => $tipo, 'estatus_id' => $estatus, 'numero_economico' => $numero,
            'placas' => $placas, 'vin' => 'VIN'.$placas, 'marca' => 'Nissan', 'modelo' => 'NP300',
            'rendimiento_optimo_km_l' => 10, 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function crearTarjeta(string $numero): int
    {
        return DB::table('tarjeta_combustibles')->insertGetId([
            'numero' => $numero, 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function asignar(int $vehiculo, int $tarjeta, bool $activo, ?string $fechaFin): void
    {
        DB::table('vehiculo_tarjetas')->insert([
            'vehiculo_id' => $vehiculo, 'tarjeta_combustible_id' => $tarjeta, 'fecha_inicio' => '2025-01-01',
            'fecha_fin' => $fechaFin, 'activo' => $activo, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function asignacionEnMemoria(TarjetaCombustible $tarjeta, Vehiculo $vehiculo, int $id): VehiculoTarjeta
    {
        $asignacion = new VehiculoTarjeta(['tarjeta_combustible_id' => $tarjeta->id, 'vehiculo_id' => $vehiculo->id]);
        $asignacion->id = $id;
        $asignacion->setRelation('tarjeta', $tarjeta);
        $asignacion->setRelation('vehiculo', $vehiculo);

        return $asignacion;
    }
}
