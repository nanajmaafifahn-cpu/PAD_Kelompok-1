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
}
