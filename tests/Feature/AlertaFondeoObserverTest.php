<?php

namespace Tests\Feature;

use App\Models\AlertaFondeo;
use App\Models\Fondeo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Notifications\AlertaFondeoMailNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertaFondeoObserverTest extends TestCase
{
    use DatabaseMigrations;

    public function test_notifica_al_creador_del_fondeo(): void
    {
        Notification::fake();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $creador = User::factory()->create([
            'email' => 'creador@acme.com',
        ]);

        $tipoVehiculoId = DB::table('tipo_vehiculos')->insertGetId([
            'nombre' => 'Pickup',
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
            'placas' => 'TST1234',
            'marca' => 'Nissan',
            'modelo' => 'NP300',
            'rendimiento_optimo_km_l' => 10.50,
            'activo' => true,
        ]);

        $fondeo = Fondeo::create([
            'vehiculo_id' => $vehiculo->id,
            'litros_fondeados' => 40.00,
            'importe_fondeado' => 920.00,
            'fecha_fondeado' => now(),
            'fondeado_por_user_id' => $creador->id,
            'comentario' => 'Prueba de fondeo',
        ]);

        $alerta = AlertaFondeo::create([
            'vehiculo_id' => $vehiculo->id,
            'fondeo_id' => $fondeo->id,
            'tipo' => 'SOBRE_FONDEO',
            'descripcion' => 'Se detecto un fondeo inusual.',
        ]);

        Notification::assertSentTo(
            $creador,
            AlertaFondeoMailNotification::class,
            function (AlertaFondeoMailNotification $notification) use ($alerta): bool {
                return $notification->alerta->is($alerta);
            }
        );
    }
}
