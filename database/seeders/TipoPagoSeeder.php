<?php

namespace Database\Seeders;

use App\Models\TipoPago;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposPago = [
            ['nombre' => 'Mensual', 'periodicidad_dias' => 30],
            ['nombre' => 'Bimestral', 'periodicidad_dias' => 60],
            ['nombre' => 'Semestral', 'periodicidad_dias' => 180],
            ['nombre' => 'Anual', 'periodicidad_dias' => 365],
        ];

        foreach ($tiposPago as $tipo) {
            TipoPago::firstOrCreate(
                ['nombre' => $tipo['nombre']],
                $tipo
            );
        }
    }
}
