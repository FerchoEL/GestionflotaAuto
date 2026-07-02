<?php

namespace Tests\Feature;

use App\Filament\Resources\FondeoResource\Pages\EditFondeo;
use App\Models\Fondeo;
use App\Models\TarjetaCombustible;
use App\Models\TarjetaSaldoMovimiento;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoTarjeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class FondeoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualiza_el_movimiento_one_card_cuando_se_edita_el_fondeo(): void
    {
        $user = User::factory()->create();

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
            'placas' => 'RE6041B',
            'marca' => 'POER',
            'modelo' => '2025',
            'rendimiento_optimo_km_l' => 10.00,
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
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $fondeo = Fondeo::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta->id,
            'litros_fondeados' => 80.000,
            'importe_fondeado' => 1919.20,
            'fecha_fondeado' => '2026-07-01 19:52:28',
            'fondeado_por_user_id' => $user->id,
            'comentario' => 'Saldo inicial',
        ]);

        $movimiento = TarjetaSaldoMovimiento::query()
            ->where('origen_tipo', Fondeo::class)
            ->where('origen_id', $fondeo->id)
            ->firstOrFail();

        $this->assertSame('1919.20', number_format((float) $movimiento->monto, 2, '.', ''));

        $fondeo->update([
            'importe_fondeado' => 2473.40,
        ]);

        $movimiento->refresh();

        $this->assertSame('2473.40', number_format((float) $movimiento->monto, 2, '.', ''));
    }

    public function test_el_formulario_de_edicion_usa_el_vehiculo_del_registro_cuando_no_llega_en_el_payload(): void
    {
        $user = User::factory()->create();

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
            'placas' => 'RE6041B',
            'marca' => 'POER',
            'modelo' => '2025',
            'rendimiento_optimo_km_l' => 10.00,
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
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'activo' => true,
        ]);

        $fondeo = Fondeo::create([
            'vehiculo_id' => $vehiculo->id,
            'tarjeta_combustible_id' => $tarjeta->id,
            'litros_fondeados' => 80.000,
            'importe_fondeado' => 1919.20,
            'fecha_fondeado' => '2026-07-01 19:52:28',
            'fondeado_por_user_id' => $user->id,
            'comentario' => 'Saldo inicial',
        ]);

        $page = app(EditFondeo::class);

        (new ReflectionProperty($page, 'record'))->setValue($page, $fondeo);

        $method = new ReflectionMethod($page, 'mutateFormDataBeforeSave');
        $method->setAccessible(true);

        $data = $method->invoke($page, [
            'fecha_fondeado' => '2026-07-01 19:52:28',
            'importe_fondeado' => 2473.40,
        ]);

        $this->assertSame($vehiculo->id, $data['vehiculo_id']);
        $this->assertSame($tarjeta->id, $data['tarjeta_combustible_id']);
    }
}
