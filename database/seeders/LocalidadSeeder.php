<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Localidad;

class LocalidadSeeder extends Seeder
{
    public function run(): void
    {
        $localidades = [
            'Monterrey',
            
        ];

        foreach ($localidades as $nombre) {
            Localidad::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}