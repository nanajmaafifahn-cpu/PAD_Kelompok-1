<?php

namespace App\Http\Requests;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembuatan jadwal perkuliahan atau praktikum baru.
 * Memastikan ruangan valid dan waktu selesai harus lebih besar dari waktu mulai.
 */
class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Schedule::class);
    }

    /**
     * Aturan validasi masukan form jadwal.
     */
    public function rules(): array
    {
        return [
            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
            ],
            'title' => [
                'required',
                'string',
                'max:150',
            ],
            'start_time' => [
                'required',
                'date',
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Ruang wajib dipilih.',
            'room_id.integer' => 'Ruang harus berupa pilihan yang valid.',
            'room_id.exists' => 'Ruang yang dipilih tidak ditemukan.',
            'title.required' => 'Nama kegiatan wajib diisi.',
            'title.string' => 'Nama kegiatan harus berupa teks.',
            'title.max' => 'Nama kegiatan maksimal 150 karakter.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date' => 'Waktu mulai harus berupa tanggal dan jam yang valid.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date' => 'Waktu selesai harus berupa tanggal dan jam yang valid.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}
