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
            'Operaciones',
            'Logística',
            'Mantenimiento',
            'Administración',
            'Finanzas',
        ];

        foreach ($departamentos as $nombre) {
            Departamento::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}