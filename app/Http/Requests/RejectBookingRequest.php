<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk penolakan peminjaman ruangan.
 * Mewajibkan input alasan penolakan (rejection_reason).
 */
class RejectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // BookingPolicy::reject (Kepala Lab / Admin); BookingService tetap mengecek ulang.
        return $this->user()->can('reject', $this->route('booking'));
    }

    /**
     * Aturan validasi alasan penolakan.
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }
}
