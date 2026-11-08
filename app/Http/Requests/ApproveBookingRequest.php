<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk otorisasi persetujuan peminjaman ruangan.
 * Memastikan request dikirim oleh user yang terotentikasi.
 */
class ApproveBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // BookingPolicy::approve (Kepala Lab / Admin); BookingService tetap mengecek ulang.
        return $this->user()->can('approve', $this->route('booking'));
    }

    public function rules(): array
    {
        return [];
    }
}
