<?php

namespace Tests\Feature;

use App\Exports\EstadoTarjetasExport;
use App\Filament\Pages\HistorialTarjetaCombustible;
use App\Filament\Pages\ReporteTarjetasCombustible;
use App\Models\User;
use App\Reports\EstadoTarjetasReport as Report;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EstadoTarjetasReportTest extends TestCase
{
    use DatabaseMigrations;

    public function test_catalogo_historico_reasignacion_y_conflictos(): void
    {
        $v1 = $this->crearVehiculo('UNO', 'ABC');
        $v2 = $this->crearVehiculo('DOS', 'DEF');
        $t1 = $this->crearTarjeta('000000000000000001');
        $t2 = $this->crearTarjeta('0002');
        $t3 = $this->crearTarjeta('0003');
        $this->asignar($v1, $t1, false, '2025-02-01');
        $this->asignar($v2, $t1, true, null);
        $this->asignar($v1, $t2, false, '2025-03-01');
        $rows = Report::query()->get()->keyBy('id');
        $this->assertCount(3, $rows);
        $this->assertSame('ASIGNADA', Report::estado($rows[$t1]));
        $this->assertSame('DOS', Report::fila($rows[$t1])[0]);
        $this->assertSame('1', Report::fila($rows[$t1])[7]);
        $this->assertSame('SIN INFORMACIÓN', Report::fila($rows[$t1])[8]);
        $this->assertSame('SIN ASIGNAR', Report::estado($rows[$t2]));
        $this->assertSame('', Report::fila($rows[$t3])[0]);
        $this->assertSame(1, Report::resumen()['Con asignación vigente']);
        // Simular un esquema legado corrupto solo en SQLite en memoria.
        DB::statement('DROP INDEX vt_activo_unico_tarjeta');
        DB::statement('DROP INDEX vt_activo_unico_vehiculo');
        $this->asignar($v1, $t1, true, null);
        $this->asignar($v1, $t2, true, null);
        $rows = Report::query()->get()->keyBy('id');
        $this->assertSame('POSIBLE DUPLICIDAD', Report::estado($rows[$t1]));
        $this->assertSame('POSIBLE DUPLICIDAD', Report::estado($rows[$t2]));
        $this->assertSame('DOS | UNO', Report::fila($rows[$t1])[0]);
        $this->assertSame(2, Report::resumen()['Tarjetas con conflictos']);
        $this->assertSame(2, Report::resumen()['Unidades con tarjeta']);
        $this->assertDatabaseCount('vehiculo_tarjetas', 5);
        // Restaurar las restricciones para el rollback de DatabaseMigrations.
        DB::table('vehiculo_tarjetas')->where('activo', true)->update(['activo' => false]);
        DB::statement('CREATE UNIQUE INDEX vt_activo_unico_tarjeta ON vehiculo_tarjetas (activo_unico_tarjeta_id)');
        DB::statement('CREATE UNIQUE INDEX vt_activo_unico_vehiculo ON vehiculo_tarjetas (activo_unico_vehiculo_id)');
    }

    public function test_cuenta_vigente_exportacion_texto_y_filtros(): void
    {
        $this->autorizar();
        $v = $this->crearVehiculo('UNO', 'ABC');
        $t = $this->crearTarjeta('000000000000000001');
        $this->crearTarjeta('0002');
        $this->asignar($v, $t, true, null);
        $cuenta = DB::table('cuenta_analiticas')->insertGetId(['nombre' => 'OPERACIÓN', 'codigo' => 'OP', 'activo' => true]);
        DB::table('vehiculo_cuenta_analiticas')->insert(['vehiculo_id' => $v, 'cuenta_analitica_id' => $cuenta, 'fecha_inicio' => '2024-01-01', 'activo' => true]);
        $this->assertSame('OPERACIÓN', Report::fila(Report::query()->find($t))[8]);
        $page = Livewire::test(ReporteTarjetasCombustible::class)
            ->assertSuccessful()->assertSee('Mostrar tarjetas')->assertCanSeeTableRecords(Report::query()->get())
            ->filterTable('estado', 'sin')->assertCountTableRecords(1)
            ->filterTable('estado', 'asignadas')->assertCountTableRecords(1)
            ->searchTable('ABC')->assertCountTableRecords(1);
        $page->sortTable('vehiculo_numero_economico')->assertCountTableRecords(1)
            ->filterTable('fecha_inicio', ['desde' => '2024-01-01', 'hasta' => '2025-12-31'])->assertCountTableRecords(1)
            ->filterTable('vehiculo_cuentaAnaliticaActiva_cuentaAnalitica_nombre', ['valor' => 'OPERA'])->assertCountTableRecords(1);
        $page->call('exportar')->assertFileDownloaded('reporte_tarjetas_combustible.xlsx');
        $export = new EstadoTarjetasExport($page->instance()->getFilteredSortedTableQuery());
        $this->assertSame(1, $export->query()->count());
        $xlsx = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'fleet-test');
        try {
            file_put_contents($file, $xlsx);
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
            $this->assertSame('000000000000000001', $sheet->getCell('F2')->getValue());
            $this->assertSame('s', $sheet->getCell('F2')->getDataType());
            $this->assertSame('OPERACIÓN', $sheet->getCell('I2')->getValue());
            $this->assertSame(2, $sheet->getHighestRow());
        } finally {
            unlink($file);
        }
        Livewire::withQueryParams(['tarjeta' => $t])->test(HistorialTarjetaCombustible::class)->assertSuccessful()->assertSee('OPERACIÓN');
    }

    public function test_permisos_restringen_pagina_historial_y_exportacion(): void
    {
        $this->assertFalse(ReporteTarjetasCombustible::canAccess());
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertFalse(ReporteTarjetasCombustible::canAccess());
        $this->assertFalse(HistorialTarjetaCombustible::canAccess());
        $this->get('/menu/reporte-tarjetas-combustible')->assertForbidden();
        Livewire::test(ReporteTarjetasCombustible::class)->assertForbidden();
        try {
            (new ReporteTarjetasCombustible)->exportar();
            $this->fail('Exportación sin autorización');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->autorizar();
        $this->assertTrue(ReporteTarjetasCombustible::canAccess());
    }

    public function test_fechas_incompatibles_y_relaciones_incompletas_no_ocultan_tarjetas(): void
    {
        $v = $this->crearVehiculo('FUTURO', 'XYZ');
        $t = $this->crearTarjeta('0007');
        $this->asignar($v, $t, true, null);
        DB::table('vehiculo_tarjetas')->where('tarjeta_combustible_id', $t)->update(['fecha_inicio' => today()->addDay()->toDateString()]);
        $this->assertSame('POSIBLE DUPLICIDAD', Report::estado(Report::query()->find($t)));
        DB::table('vehiculo_tarjetas')->where('tarjeta_combustible_id', $t)->update(['fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-02-01']);
        $this->assertSame('POSIBLE DUPLICIDAD', Report::estado(Report::query()->find($t)));
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        try {
            DB::table('vehiculo_tarjetas')->where('tarjeta_combustible_id', $t)->update(['vehiculo_id' => 999999, 'fecha_fin' => null]);
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }
        $row = Report::query()->findOrFail($t);
        $this->assertSame('POSIBLE DUPLICIDAD', Report::estado($row));
        $this->assertSame('SIN INFORMACIÓN', Report::fila($row)[8]);
        $this->assertSame(1, Report::resumen()['Total de tarjetas']);
    }

    public function test_historial_muestra_cuentas_por_periodo_y_activo_real(): void
    {
        $this->autorizar();
        $v = $this->crearVehiculo('HIST', 'HIS');
        $t = $this->crearTarjeta('0008');
        $this->asignar($v, $t, false, '2025-03-01');
        $cuenta = DB::table('cuenta_analiticas')->insertGetId(['nombre' => 'CUENTA ANTERIOR', 'codigo' => 'ANT', 'activo' => false]);
        DB::table('vehiculo_cuenta_analiticas')->insert(['vehiculo_id' => $v, 'cuenta_analitica_id' => $cuenta, 'fecha_inicio' => '2024-01-01', 'fecha_fin' => '2025-06-01', 'activo' => false]);
        $this->assertSame('SIN ASIGNAR', Report::estado(Report::query()->find($t)));
        Livewire::withQueryParams(['tarjeta' => $t])->test(HistorialTarjetaCombustible::class)
            ->assertSuccessful()->assertCountTableRecords(1)->assertSee('CUENTA ANTERIOR')->assertSee('ASIGNACIÓN HISTÓRICA');
        // Una cuenta sin periodo compatible no se atribuye retroactivamente.
        DB::table('vehiculo_cuenta_analiticas')->update(['fecha_inicio' => '2025-02-01']);
        Livewire::withQueryParams(['tarjeta' => $t])->test(HistorialTarjetaCombustible::class)->assertSee('SIN INFORMACIÓN');
    }

    private function autorizar(): void
    {
        $user = User::factory()->create();
        foreach (['tarjeta-combustible.view', 'vehiculo-tarjeta.view'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
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
}
