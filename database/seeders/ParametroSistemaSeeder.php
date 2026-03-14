<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParametroSistema;

class ParametroSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            [
                'clave' => 'umbral_rendimiento_pct',
                'valor' => '10',
                'descripcion' => 'Porcentaje permitido por debajo del rendimiento óptimo',
            ],
            [
                'clave' => 'dias_alerta_sin_cierre',
                'valor' => '7',
                'descripcion' => 'Días máximos para cerrar una alerta',
            ],
        ];

        foreach ($parametros as $p) {
            ParametroSistema::firstOrCreate(
                ['clave' => $p['clave']],
                [
                    'valor' => $p['valor'],
                    'descripcion' => $p['descripcion'],
                    'activo' => true,
                ]
            );
        }
    }
}