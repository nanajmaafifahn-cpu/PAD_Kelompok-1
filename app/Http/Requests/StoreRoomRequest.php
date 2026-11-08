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

    public function messages(): array
    {
        return [
            'academic_unit_id.required' => 'Unit akademik wajib dipilih.',
            'academic_unit_id.integer' => 'Unit akademik harus berupa pilihan yang valid.',
            'academic_unit_id.exists' => 'Unit akademik yang dipilih tidak ditemukan.',
            'code.required' => 'Kode ruang wajib diisi.',
            'code.string' => 'Kode ruang harus berupa teks.',
            'code.max' => 'Kode ruang maksimal 30 karakter.',
            'code.unique' => 'Kode ruang sudah digunakan.',
            'type.required' => 'Tipe ruang wajib dipilih.',
            'type.in' => 'Tipe ruang yang dipilih tidak valid.',
            'name.required' => 'Nama ruang wajib diisi.',
            'name.string' => 'Nama ruang harus berupa teks.',
            'name.max' => 'Nama ruang maksimal 150 karakter.',
            'building.required' => 'Gedung wajib diisi.',
            'building.string' => 'Gedung harus berupa teks.',
            'building.max' => 'Gedung maksimal 100 karakter.',
            'floor.string' => 'Lantai harus berupa teks.',
            'floor.max' => 'Lantai maksimal 30 karakter.',
            'capacity.required' => 'Kapasitas ruang wajib diisi.',
            'capacity.integer' => 'Kapasitas ruang harus berupa bilangan bulat.',
            'capacity.min' => 'Kapasitas ruang minimal 1 orang.',
            'status.required' => 'Status ruang wajib dipilih.',
            'status.in' => 'Status ruang yang dipilih tidak valid.',
            'description.string' => 'Deskripsi ruang harus berupa teks.',
            'photo.image' => 'Foto ruang harus berupa gambar.',
            'photo.mimes' => 'Foto ruang harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto ruang maksimal 2 MB.',
            'photo.uploaded' => 'Foto ruang gagal diunggah. Pilih kembali foto dan coba lagi.',
            'remove_photo.boolean' => 'Pilihan hapus foto tidak valid.',
        ];
    }

    // UpdateRoomRequest mengabaikan ruang yang sedang diubah.
    protected function uniqueCodeRule(): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique('rooms', 'code');
    }
}
