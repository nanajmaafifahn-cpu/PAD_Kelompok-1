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

        // Akun berpassword "password" tidak boleh ikut ke server produksi.
        if (app()->environment('local', 'testing')) {
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => bcrypt('password')]
            );

            $this->call(DemoAccountSeeder::class);
        }

        \App\Models\AcademicUnit::firstOrCreate(['code' => 'FRI'], ['name' => 'Fakultas Rekayasa Industri']);
        \App\Models\AcademicUnit::firstOrCreate(['code' => 'FIF'], ['name' => 'Fakultas Informatika']);
        \App\Models\AcademicUnit::firstOrCreate(['code' => 'FTE'], ['name' => 'Fakultas Teknik Elektro']);
    }
}
