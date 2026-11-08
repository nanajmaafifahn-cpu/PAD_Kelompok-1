<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends BaseCampusEntity
{
    public const STATUS_LABELS = [
        'scheduled' => 'Terjadwal',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'room_id',
        'title',
        'start_time',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function getEntitySummary(): string
    {
        return "Jadwal: {$this->title}, Ruangan: {$this->room?->name}";
    }
}