<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin',
            'activos',
            'administracion',
            'responsable',
            'chofer',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

         $admin = User::updateOrCreate(
            ['email' => 'fernando.espinosa@kpgroup.mx'],
            [
                'name' => 'Fernando Espinosa',
                'password' => Hash::make('Fercho92'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');
    }
}
