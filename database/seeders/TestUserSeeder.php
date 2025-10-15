<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrador User
        $admin = User::firstOrCreate(
            ['email' => 'admin@localhost.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('admin'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('Administrador');

        // Gerente Financiero User
        $gerente = User::firstOrCreate(
            ['email' => 'gerente@localhost.com'],
            [
                'name' => 'Gerente Financiero',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $gerente->assignRole('Gerente Financiero');

        // Analista de Datos User
        $analista = User::firstOrCreate(
            ['email' => 'analista@localhost.com'],
            [
                'name' => 'Analista de Datos',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $analista->assignRole('Analista de Datos');

        // Auditor User
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@localhost.com'],
            [
                'name' => 'Auditor User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $auditor->assignRole('Auditor');
    }
}
