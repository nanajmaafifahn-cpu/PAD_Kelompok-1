<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembuatan data ruangan master baru.
 * Memastikan kapasitas ruangan valid, kode unik, dan status berada dalam daftar yang diizinkan.
 */
class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Room::class);
    }

    /**
     * Aturan validasi masukan form ruangan.
     */
    public function rules(): array
    {
        return [
            'academic_unit_id' => [
                'required',
                'integer',
                'exists:academic_units,id',
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                $this->uniqueCodeRule(),
            ],
            // Kunci Strategy utilisasi: laboratory | classroom (label UI di Room::TYPE_LABELS).
            'type' => [
                'required',
                Rule::in(array_keys(Room::TYPE_LABELS)),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'building' => [
                'required',
                'string',
                'max:100',
            ],
            'floor' => [
                'nullable',
                'string',
                'max:30',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],
            // Hanya available / maintenance; "occupied" dihitung otomatis dari check-in (D4).
            'status' => [
                'required',
                Rule::in(Room::STATUSES),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            // Foto ruang: jpg/png/webp maks 2 MB, disimpan di disk public (RoomController).
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    // UpdateRoomRequest mengabaikan ruang yang sedang diubah.
    protected function uniqueCodeRule(): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique('rooms', 'code');
    }
}
