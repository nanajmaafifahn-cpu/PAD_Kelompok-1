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
                Rule::unique('rooms', 'code'),
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
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance',
                ]),
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
