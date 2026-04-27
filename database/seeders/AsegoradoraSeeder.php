<?php

namespace Database\Seeders;

use App\Models\Aseguradora;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AsegoradoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aseguradoras = [
            [
                'nombre' => 'AXA',
                'numero_telefonico' => '+34 901 234 567',
                'email' => 'info@axa.es',
                'descripcion' => 'Aseguradora AXA',
                'activo' => true,
            ],
            [
                'nombre' => 'Allianz',
                'numero_telefonico' => '+34 902 345 678',
                'email' => 'contacto@allianz.es',
                'descripcion' => 'Aseguradora Allianz',
                'activo' => true,
            ],
            [
                'nombre' => 'MAPFRE',
                'numero_telefonico' => '+34 903 456 789',
                'email' => 'info@mapfre.es',
                'descripcion' => 'Aseguradora MAPFRE',
                'activo' => true,
            ],
            [
                'nombre' => 'Generali',
                'numero_telefonico' => '+34 904 567 890',
                'email' => 'contacto@generali.es',
                'descripcion' => 'Aseguradora Generali',
                'activo' => true,
            ],
            [
                'nombre' => 'Zurich',
                'numero_telefonico' => '+34 905 678 901',
                'email' => 'info@zurich.es',
                'descripcion' => 'Aseguradora Zurich',
                'activo' => true,
            ],
        ];

        foreach ($aseguradoras as $aseguradora) {
            Aseguradora::firstOrCreate(
                ['nombre' => $aseguradora['nombre']],
                $aseguradora
            );
        }
    }
}
