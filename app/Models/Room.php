<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    // Token QR tidak pernah diisi dari form dan tidak ikut diserialisasi.
    protected $hidden = [
        'qr_token',
    ];

    protected $fillable = [
        'academic_unit_id',
        'code',
        'name',
        'building',
        'floor',
        'capacity',
        'status',
        'description',
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
