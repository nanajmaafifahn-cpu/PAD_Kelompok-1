<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_BORROWER = 'borrower';
    public const ROLE_LAB_HEAD = 'lab_head';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LEADER = 'leader';

    // Satu-satunya tempat label role berbahasa Indonesia (dipakai view & requester_role booking).
    public const ROLE_LABELS = [
        self::ROLE_BORROWER => 'Mahasiswa / Dosen',
        self::ROLE_LAB_HEAD => 'Pengelola Ruang / Kepala Lab',
        self::ROLE_ADMIN => 'Admin Akademik',
        self::ROLE_LEADER => 'Pimpinan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Akun dinonaktifkan admin tidak bisa login (default kolom: aktif).
    public function isActive(): bool
    {
        return (bool) ($this->getAttribute('is_active') ?? true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->getAttribute('role'), $roles, true);
    }

    // Inisial avatar: huruf pertama dua kata pertama nama (mis. "Andi Pratama" -> "AP").
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('') ?: '?';
    }

    public function roleLabel(): string
    {
        $role = (string) $this->getAttribute('role');

        return self::ROLE_LABELS[$role] ?? $role;
    }

    public function canApproveBookings(): bool
    {
        return $this->hasRole(self::ROLE_LAB_HEAD, self::ROLE_ADMIN);
    }
}
