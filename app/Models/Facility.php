<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facility extends BaseCampusEntity
{
    private int $minimumQuantity = 0;

    // Kondisi fasilitas: kunci DB => label UI.
    public const STATUS_LABELS = [
        'available' => 'Baik',
        'damaged' => 'Rusak',
        'maintenance' => 'Perawatan',
    ];

    private array $allowedStatuses = [
        'available',
        'damaged',
        'maintenance',
    ];

    protected $fillable = [
        'room_id',
        'name',
        'quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // Relasi ke ruangan
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Getter jumlah fasilitas
    public function getQuantity(): int
    {
        return (int) $this->getAttribute('quantity');
    }

    // Setter jumlah fasilitas
    public function setQuantity(int $quantity): void
    {
        $this->setAttribute('quantity', $quantity);
    }

    // Getter status fasilitas
    public function getStatus(): string
    {
        return (string) $this->getAttribute('status');
    }

    // Setter status fasilitas
    public function setStatus(string $status): void
    {
        $this->setAttribute('status', $status);
    }

    // Mutator: validasi jumlah fasilitas
    protected function quantity(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $value = filter_var(
                    $value,
                    FILTER_VALIDATE_INT
                );

                if (
                    $value === false ||
                    $value < $this->minimumQuantity
                ) {
                    throw new InvalidArgumentException(
                        'Jumlah fasilitas tidak boleh negatif.'
                    );
                }

                return $value;
            }
        );
    }

    // Mutator: validasi status fasilitas
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
                        'Status fasilitas tidak valid.'
                    );
                }

                return $value;
            }
        );
    }

    // Implementasi abstract method dan polymorphism
    public function getEntitySummary(): string
    {
        return "Fasilitas: {$this->name}, "
            . "Jumlah: {$this->getQuantity()} unit";
    }
}
