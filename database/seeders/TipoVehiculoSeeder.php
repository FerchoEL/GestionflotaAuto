<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\TipoVehiculo;

class TipoVehiculoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Automóvil',
            'Camioneta',
            
            
        ];

        foreach ($tipos as $nombre) {
            TipoVehiculo::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}