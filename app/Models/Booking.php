<?php

namespace App\Models;

use App\Exceptions\BookingException;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class Booking extends BaseCampusEntity
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    public const APPROVED_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_CHECKED_IN,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    // Label status untuk UI (satu sumber; dipakai x-status-badge & timeline).
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu persetujuan',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_CHECKED_IN => 'Check-in',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_NO_SHOW => 'Tidak hadir',
    ];

    // Booking yang boleh dihapus pengelola: tidak ikut perhitungan utilisasi.
    public const DELETABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    // Jendela check-in = [mulai - 15 menit, mulai + 15 menit].
    // Batas akhirnya sengaja sama dengan toleransi ghost booking: lewat dari itu booking
    // dianggap tidak hadir (no_show), jadi check-in tidak boleh lagi diterima.
    public const CHECK_IN_OPENS_BEFORE_MINUTES = 15;

    public const GHOST_TOLERANCE_MINUTES = 15;

    // Kode cadangan QR hanya ditampilkan ke pemiliknya (detail booking), tidak diserialisasi.
    protected $hidden = [
        'qr_token',
    ];

    protected $fillable = [
        'room_id',
        'user_id',
        'requester_name',
        'requester_role',
        'purpose',
        'participant_count',
        'start_time',
        'end_time',
        'status',
        'qr_token',
        'checked_in_at',
        'checked_out_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'participant_count' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatus(): string
    {
        return (string) $this->getAttribute('status');
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_CHECKED_IN,
            self::STATUS_COMPLETED,
            self::STATUS_NO_SHOW,
        ], true)) {
            throw new InvalidArgumentException('Status booking tidak valid.');
        }

        $this->setAttribute('status', $status);
    }

    public function isGhostBooking(?CarbonInterface $now = null): bool
    {
        if ($this->getStatus() === self::STATUS_NO_SHOW) {
            return true;
        }

        $start = $this->getAttribute('start_time');

        return $this->getStatus() === self::STATUS_APPROVED
            && $this->getAttribute('checked_in_at') === null
            && $start !== null
            && $start->copy()
                ->addMinutes(self::GHOST_TOLERANCE_MINUTES)
                ->lt($now ?? now());
    }

    public function detectAndMarkGhostBooking(?CarbonInterface $now = null): bool
    {
        if ($this->getStatus() !== self::STATUS_APPROVED) {
            return false;
        }

        if (!$this->isGhostBooking($now)) {
            return false;
        }

        $this->setStatus(self::STATUS_NO_SHOW);
        $this->save();

        return true;
    }

    public function hasScheduleConflict(): bool
    {
        $start = $this->getAttribute('start_time');
        $end = $this->getAttribute('end_time');

        if ($start === null || $end === null) {
            return false;
        }

        $overlapsSchedule = Schedule::query()
            ->where('room_id', $this->getAttribute('room_id'))
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($overlapsSchedule) {
            return true;
        }

        $bookings = static::query()
            ->where('room_id', $this->getAttribute('room_id'))
            ->whereIn('status', [
                self::STATUS_APPROVED,
                self::STATUS_CHECKED_IN,
            ])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($this->exists) {
            $bookings->whereKeyNot($this->getKey());
        }

        return $bookings->exists();
    }

    /**
     * Langkah timeline detail booking: Diajukan -> Disetujui/Ditolak/Dibatalkan -> Check-in -> Selesai.
     * state: done | current | upcoming | failed. Waktu persetujuan belum disimpan (tanpa approved_at).
     *
     * @return list<array{label: string, state: string, at: ?CarbonInterface, note: ?string}>
     */
    public function timelineSteps(): array
    {
        $status = $this->getStatus();
        $step = fn (string $label, string $state, ?CarbonInterface $at = null, ?string $note = null) =>
            ['label' => $label, 'state' => $state, 'at' => $at, 'note' => $note];

        $steps = [$step('Diajukan', 'done', $this->created_at)];

        if ($status === self::STATUS_REJECTED) {
            return [...$steps, $step('Ditolak', 'failed', $this->updated_at, $this->rejection_reason)];
        }

        if ($status === self::STATUS_CANCELLED) {
            return [...$steps, $step('Dibatalkan', 'failed', $this->updated_at)];
        }

        $approved = in_array($status, self::APPROVED_STATUSES, true);
        $steps[] = $step('Disetujui', $approved ? 'done' : 'current');

        if ($status === self::STATUS_NO_SHOW) {
            return [...$steps, $step('Tidak hadir', 'failed', null, 'Tidak check-in sampai batas waktu.')];
        }

        $checkedIn = $this->checked_in_at !== null;
        $steps[] = $step('Check-in', $checkedIn ? 'done' : ($status === self::STATUS_APPROVED ? 'current' : 'upcoming'), $this->checked_in_at);
        $steps[] = $step('Selesai', $status === self::STATUS_COMPLETED ? 'done' : ($checkedIn ? 'current' : 'upcoming'), $this->checked_out_at);

        return $steps;
    }

    public function checkInOpensAt(): CarbonInterface
    {
        return $this->start_time->copy()->subMinutes(self::CHECK_IN_OPENS_BEFORE_MINUTES);
    }

    public function checkInClosesAt(): CarbonInterface
    {
        return $this->start_time->copy()->addMinutes(self::GHOST_TOLERANCE_MINUTES);
    }

    // Check-in lewat kode cadangan booking (input manual).
    public function performQrCheckIn(
        string $qrToken,
        ?CarbonInterface $at = null
    ): void {
        $this->assertQrToken($qrToken);
        $this->performCheckIn($at);
    }

    // Check-in tanpa token booking: dipakai alur scan QR ruangan, yang sudah
    // memastikan pemilik booking & ruangannya di BookingService::scanRoom.
    public function performCheckIn(?CarbonInterface $at = null): void
    {
        if ($this->getStatus() !== self::STATUS_APPROVED) {
            throw BookingException::invalidState(
                'check-in',
                $this->getStatus()
            );
        }

        if ($this->getAttribute('checked_in_at') !== null) {
            throw BookingException::alreadyCheckedIn();
        }

        $at ??= now();

        if ($at->lt($this->checkInOpensAt())) {
            throw BookingException::checkInNotYetOpen($this->checkInOpensAt());
        }

        if ($at->gt($this->checkInClosesAt())) {
            throw BookingException::checkInClosed($this->checkInClosesAt());
        }

        $this->setAttribute('checked_in_at', $at);
        $this->setStatus(self::STATUS_CHECKED_IN);
        $this->save();
    }

    public function performQrCheckOut(
        string $qrToken,
        ?CarbonInterface $at = null
    ): void {
        $this->assertQrToken($qrToken);
        $this->performCheckOut($at);
    }

    public function performCheckOut(?CarbonInterface $at = null): void
    {
        if ($this->getStatus() !== self::STATUS_CHECKED_IN) {
            throw BookingException::invalidState(
                'check-out',
                $this->getStatus()
            );
        }

        if ($this->getAttribute('checked_out_at') !== null) {
            throw BookingException::alreadyCheckedOut();
        }

        $this->setAttribute('checked_out_at', $at ?? now());
        $this->setStatus(self::STATUS_COMPLETED);
        $this->save();
    }

    private function assertQrToken(string $qrToken): void
    {
        $stored = $this->getAttribute('qr_token');

        if ($stored === null || !hash_equals((string) $stored, $qrToken)) {
            throw BookingException::invalidQrToken();
        }
    }

    public function getEntitySummary(): string
    {
        return "Peminjaman: {$this->purpose}, Status: {$this->status}";
    }
}