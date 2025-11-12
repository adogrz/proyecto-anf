<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(TestUserSeeder::class);
        $this->call(CatalogoBaseSeeder::class);
        $this->call(DemoDataSeeder::class);
        $this->call(TestBalanceGeneralSeeder::class);

        // Para analisis de Ratios
        $this->call(RatiosSectorSeeder::class);
        $this->call(RatiosCalculadosSeeder::class);
    }
}
