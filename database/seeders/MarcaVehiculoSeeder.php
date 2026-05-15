<?php

namespace Database\Seeders;

use App\Models\MarcaVehiculo;
use Illuminate\Database\Seeder;

class MarcaVehiculoSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Nissan',
            'Chevrolet',
            'Ford',
            'Toyota',
            'Volkswagen',
            'RAM',
            'Dodge',
            'Chrysler',
            'Jeep',
            'Hyundai',
            'Kia',
            'Honda',
        ];

        foreach ($brands as $nombre) {
            MarcaVehiculo::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}
