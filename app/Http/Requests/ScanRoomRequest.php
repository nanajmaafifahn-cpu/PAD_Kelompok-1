<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfirmasi check-in / check-out hasil scan QR ruangan.
 * Kepemilikan booking dicek BookingService::scanRoom (hanya booking milik user login).
 */
class ScanRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('scan', Booking::class);
    }

    public function rules(): array
    {
        return [];
    }
}
