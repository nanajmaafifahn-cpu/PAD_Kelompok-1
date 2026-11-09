<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan booking oleh user login. Nama & peran pemohon tidak diisi bebas,
 * melainkan diambil dari akun yang login di BookingService::submitBooking.
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    public function rules(): array
    {
        return [
            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
            ],
            'participant_count' => [
                'required',
                'integer',
                'min:1',
            ],
            // D3: tidak boleh di masa lalu (dicek ulang di BookingService).
            'start_time' => [
                'required',
                'date',
                'after:now',
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
            ],
            'purpose' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Ruang wajib dipilih.',
            'room_id.integer' => 'Ruang harus berupa pilihan yang valid.',
            'room_id.exists' => 'Ruang yang dipilih tidak ditemukan.',
            'participant_count.required' => 'Jumlah peserta wajib diisi.',
            'participant_count.integer' => 'Jumlah peserta harus berupa bilangan bulat.',
            'participant_count.min' => 'Jumlah peserta minimal 1 orang.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date' => 'Waktu mulai harus berupa tanggal dan jam yang valid.',
            'start_time.after' => 'Waktu mulai harus setelah saat ini.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date' => 'Waktu selesai harus berupa tanggal dan jam yang valid.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'purpose.required' => 'Keperluan peminjaman wajib diisi.',
            'purpose.string' => 'Keperluan peminjaman harus berupa teks.',
            'purpose.max' => 'Keperluan peminjaman maksimal 255 karakter.',
        ];
    }
}
