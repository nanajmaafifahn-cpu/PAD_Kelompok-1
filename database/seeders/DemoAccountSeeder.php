<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo per role, password semua akun: password.
 * Hanya dipanggil DatabaseSeeder saat APP_ENV=local atau testing.
 */
class DemoAccountSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** email => [nama, role] */
    public const ACCOUNTS = [
        'mahasiswa@campus.test' => ['Andi Pratama (Mahasiswa)', User::ROLE_BORROWER],
        'dosen@campus.test' => ['Dr. Sinta Rahmawati (Dosen)', User::ROLE_BORROWER],
        'pengelola@campus.test' => ['Kepala Laboratorium FRI', User::ROLE_LAB_HEAD],
        'admin@campus.test' => ['Admin Akademik FRI', User::ROLE_ADMIN],
        'pimpinan@campus.test' => ['Wakil Dekan II FRI', User::ROLE_LEADER],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $email => [$name, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => $role, 'password' => self::PASSWORD]
            );
        }
    }
}
