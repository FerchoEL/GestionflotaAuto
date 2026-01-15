<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MotivoAuditoria;

class MotivoAuditoriaSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Posible desvío de combustible',
            'Uso fuera de ruta',
            'Conducción ineficiente',
            'Falla mecánica',
            'Carga incompleta',
        ];

        foreach ($motivos as $nombre) {
            MotivoAuditoria::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}
