<?php

namespace Database\Seeders;

use App\Models\TipoDocumento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tipo de documento para Póliza de Seguro
        TipoDocumento::firstOrCreate(
            ['nombre' => 'Póliza de Seguro'],
            [
                'requiere_vigencia' => true,
                'dias_alerta_previa' => 30,
                'es_obligatorio' => true,
                'es_poliza_seguro' => true,
            ]
        );
    }
}
