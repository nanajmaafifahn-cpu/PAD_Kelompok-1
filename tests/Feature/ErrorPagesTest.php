<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PROMPT 2 Tahap 5: halaman galat HTTP berbahasa Indonesia dan bergaya Campus Space. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_403_dari_middleware_role_menampilkan_pesan_indonesia(): void
    {
        $borrower = User::factory()->create(['role' => User::ROLE_BORROWER]);

        $this->actingAs($borrower)->get(route('rooms.manage'))
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('Anda tidak memiliki akses ke halaman ini.')
            ->assertSee('Kembali ke Dashboard')
            ->assertDontSee('This action is unauthorized');
    }

    public function test_404_menampilkan_halaman_tidak_ditemukan(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Halaman yang Anda cari tidak ada atau sudah dihapus.')
            ->assertSee('Masuk')
            ->assertDontSee('could not be found');
    }
}
