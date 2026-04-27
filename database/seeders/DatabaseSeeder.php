<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@ruta66.local',
                'cedula_identidad' => '12345678',
                'nombres' => 'Admin',
                'apellido_paterno' => 'User',
                'apellido_materno' => '',
                'foto' => null,
                'password' => bcrypt('password'),
            ],
        );

        $admin->syncRoles(['super_admin']);
    }
}
