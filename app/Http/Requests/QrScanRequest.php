<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Simulasi scan QR (check-in / check-out): isi QR adalah token booking.
 */
class QrScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // BookingPolicy::checkIn / checkOut: hanya pemilik booking.
        $ability = $this->routeIs('bookings.checkout') ? 'checkOut' : 'checkIn';

        return $this->user()->can($ability, $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'qr_token' => [
                'required',
                'string',
                'max:64',
            ],
        ];
    }
}
