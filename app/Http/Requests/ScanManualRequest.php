<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Input manual di halaman pemindai: tempel URL QR ruangan atau ketik kode cadangan booking.
 */
class ScanManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('scan', Booking::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'URL atau kode QR'];
    }
}
