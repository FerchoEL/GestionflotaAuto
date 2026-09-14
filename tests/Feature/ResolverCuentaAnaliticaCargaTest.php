<?php

namespace Tests\Feature;

use App\Models\CuentaAnalitica;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoCuentaAnalitica;
use App\Services\ResolverCuentaAnaliticaCarga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResolverCuentaAnaliticaCargaTest extends TestCase
{
    use RefreshDatabase;

    public function test_chofer_estricto_ignora_la_cuenta_recibida_y_usa_la_predeterminada(): void
    {
        $chofer = $this->userWithRole('chofer');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $manipulada = CuentaAnalitica::create(['nombre' => 'Cuenta B', 'activo' => true]);
        $vehiculo = $this->vehicleWithAccount($predeterminada);

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($chofer, $vehiculo, $manipulada->id);

        $this->assertSame($predeterminada->id, $resultado);
    }

    public function test_chofer_estricto_sin_cuenta_predeterminada_conserva_null(): void
    {
        $chofer = $this->userWithRole('chofer');
        $vehiculo = $this->vehicleWithAccount();

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($chofer, $vehiculo, 999);

        $this->assertNull($resultado);
    }

    public function test_usuario_autorizado_conserva_su_seleccion_explicita(): void
    {
        $responsable = $this->userWithRole('responsable');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $seleccionada = CuentaAnalitica::create(['nombre' => 'Cuenta B', 'activo' => true]);
        $vehiculo = $this->vehicleWithAccount($predeterminada);

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($responsable, $vehiculo, $seleccionada->id);

        $this->assertSame($seleccionada->id, $resultado);
    }

    public function test_usuario_autorizado_sin_seleccion_usa_la_predeterminada(): void
    {
        $auxiliar = $this->userWithRole('auxiliar_responsable');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $vehiculo = $this->vehicleWithAccount($predeterminada);

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($auxiliar, $vehiculo);

        $this->assertSame($predeterminada->id, $resultado);
    }

    public function test_administrador_con_seleccion_explicita_conserva_la_seleccion(): void
    {
        $administrador = $this->userWithRole('admin');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $seleccionada = CuentaAnalitica::create(['nombre' => 'Cuenta B', 'activo' => true]);
        $vehiculo = $this->vehicleWithAccount($predeterminada);

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($administrador, $vehiculo, $seleccionada->id);

        $this->assertSame($seleccionada->id, $resultado);
    }

    public function test_administrador_sin_seleccion_usa_la_predeterminada(): void
    {
        $administrador = $this->userWithRole('admin');
        $predeterminada = CuentaAnalitica::create(['nombre' => 'Cuenta A', 'activo' => true]);
        $vehiculo = $this->vehicleWithAccount($predeterminada);

        $resultado = app(ResolverCuentaAnaliticaCarga::class)->resolver($administrador, $vehiculo);

        $this->assertSame($predeterminada->id, $resultado);
    }

    private function userWithRole(string $role): User
    {
        $role = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function vehicleWithAccount(?CuentaAnalitica $account = null): Vehiculo
    {
        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Prueba',
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
            'placas' => 'TEST-' . random_int(1000, 9999),
            'marca' => 'Prueba',
            'modelo' => 'Prueba',
            'rendimiento_optimo_km_l' => 10,
            'activo' => true,
        ]);

        if ($account) {
            VehiculoCuentaAnalitica::create([
                'vehiculo_id' => $vehiculo->id,
                'cuenta_analitica_id' => $account->id,
                'fecha_inicio' => now()->toDateString(),
                'activo' => true,
            ]);
        }

        return $vehiculo->fresh();
    }
}
