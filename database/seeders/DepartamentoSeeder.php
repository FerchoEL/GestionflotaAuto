<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Departamento;

class DepartamentoSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = [
            'Dirección',
            'Administración',
            'Infraestructura',
            
        ];

        foreach ($departamentos as $nombre) {
            Departamento::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}