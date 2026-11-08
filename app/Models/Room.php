<?php

namespace App\Models;

use App\Strategies\ClassroomUtilizationStrategy;
use App\Strategies\LaboratoryUtilizationStrategy;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Room extends BaseCampusEntity
{
    private int $minimumCapacity = 1;

    // Status yang disimpan hanya available / maintenance. "occupied" & "booked"
    // diturunkan otomatis dari booking lewat getLiveStatus() (D4).
    public const STATUSES = [
        'available',
        'maintenance',
    ];

    private array $allowedStatuses = self::STATUSES;

    // Status live (dihitung, tidak disimpan) dan labelnya untuk UI.
    public const LIVE_AVAILABLE = 'available';
    public const LIVE_BOOKED = 'booked';
    public const LIVE_OCCUPIED = 'occupied';
    public const LIVE_MAINTENANCE = 'maintenance';

    public const LIVE_STATUS_LABELS = [
        self::LIVE_AVAILABLE => 'Tersedia sekarang',
        self::LIVE_OCCUPIED => 'Sedang digunakan',
        self::LIVE_BOOKED => 'Dipesan ≤2 jam',
        self::LIVE_MAINTENANCE => 'Perawatan',
    ];

    // Nilai DB = kunci Strategy utilisasi (UtilizationCalculatorContext memilih strategi dari sini).
    // Label UI hanya ditentukan di sini. Lihat penjelasan pemetaan di docs/fe/KONTRAK-VIEW.md.
    public const TYPE_LABELS = [
        LaboratoryUtilizationStrategy::ROOM_TYPE => 'Computer Lab',
        ClassroomUtilizationStrategy::ROOM_TYPE => 'Class Lab',
    ];

    // Token QR tidak pernah diisi dari form dan tidak ikut diserialisasi.
    protected $hidden = [
        'qr_token',
    ];

    protected $fillable = [
        'academic_unit_id',
        'code',
        'type',
        'name',
        'building',
        'floor',
        'capacity',
        'status',
        'description',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    // Setiap ruang baru langsung punya token QR (isi QR = route('scan.room', qr_token)).
    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->qr_token ??= self::newQrToken();
        });
    }

    public static function newQrToken(): string
    {
        return Str::random(40);
    }

    /**
     * Jumlah komputer = jumlah fasilitas yang namanya mengandung "PC" / "Komputer" / "Computer".
     * Memakai relasi facilities yang sudah di-eager-load (tanpa query tambahan bila sudah dimuat).
     * 0 berarti ruang nonkomputer.
     */
    public function computerCount(): int
    {
        return (int) $this->facilities
            ->filter(fn (Facility $f) => preg_match('/\b(pc|komputer|computer)\b/i', (string) $f->name) === 1)
            ->sum(fn (Facility $f) => $f->getQuantity());
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[(string) $this->type] ?? (string) $this->type;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /**
     * Filter daftar ruang dari query string (E4):
     * q (nama/kode), type, min_capacity, facility (nama fasilitas).
     *
     * @param array{q?:?string,type?:?string,min_capacity?:?int,facility?:?string} $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(
                fn (Builder $w) => $w->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['min_capacity'] ?? null, fn (Builder $q, $min) => $q->where('capacity', '>=', (int) $min))
            ->when($filters['facility'] ?? null, fn (Builder $q, string $name) => $q->whereHas(
                'facilities',
                fn (Builder $f) => $f->where('name', $name)
            ));
    }

    // Aksi "Perbarui QR": QR lama yang sudah tercetak tidak berlaku lagi.
    public function regenerateQrToken(): void
    {
        $this->qr_token = self::newQrToken();
        $this->save();
    }

    // Relasi ke unit akademik
    public function academicUnit(): BelongsTo
    {
        return $this->belongsTo(AcademicUnit::class);
    }

    // Relasi ke fasilitas
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    // Relasi ke jadwal
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Relasi ke peminjaman
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Getter kapasitas
    public function getCapacity(): int
    {
        return (int) $this->getAttribute('capacity');
    }

    // Setter kapasitas
    public function setCapacity(int $capacity): void
    {
        $this->setAttribute('capacity', $capacity);
    }

    // Getter status
    public function getStatus(): string
    {
        return (string) $this->getAttribute('status');
    }

    // Setter status
    public function setStatus(string $status): void
    {
        $this->setAttribute('status', $status);
    }

    // Status pemakaian ruangan saat ini untuk dashboard live (D4):
    // occupied = ada peminjam yang sudah check-in (QR) dan jadwalnya belum selesai;
    // booked   = ada booking approved yang sedang berjalan / mulai dalam 2 jam, tapi belum check-in.
    public function getLiveStatus(): string
    {
        if ($this->getStatus() === 'maintenance') {
            return 'maintenance';
        }

        $now = now();

        $occupied = $this->bookings()
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->where('end_time', '>=', $now)
            ->exists();

        if ($occupied) {
            return 'occupied';
        }

        $booked = $this->bookings()
            ->where('status', Booking::STATUS_APPROVED)
            ->where('end_time', '>=', $now)
            ->where('start_time', '<=', $now->copy()->addHours(2))
            ->exists();

        return $booked ? 'booked' : 'available';
    }

    // Mutator: validasi kapasitas untuk seluruh assignment Eloquent
    protected function capacity(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $value = filter_var(
                    $value,
                    FILTER_VALIDATE_INT
                );

                if (
                    $value === false ||
                    $value < $this->minimumCapacity
                ) {
                    throw new InvalidArgumentException(
                        'Kapasitas ruangan minimal 1 orang.'
                    );
                }

                return $value;
            }
        );
    }

    // Mutator: validasi status
    protected function status(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (!in_array(
                    $value,
                    $this->allowedStatuses,
                    true
                )) {
                    throw new InvalidArgumentException(
                        'Status ruangan tidak valid.'
                    );
                }

                return $value;
            }
        );
    }

    // Implementasi abstract method dan polymorphism
    public function getEntitySummary(): string
    {
        return "Ruangan: {$this->name}, "
            . "Kapasitas: {$this->getCapacity()} orang";
    }
}
