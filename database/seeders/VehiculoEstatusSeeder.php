<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehiculoEstatus;

class VehiculoEstatusSeeder extends Seeder
{
    public function run(): void
    {
        $estatuses = [
            'Activo',
            'En mantenimiento',
            'Fuera de servicio',
            'Baja',
        ];

        foreach ($estatuses as $nombre) {
            VehiculoEstatus::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}