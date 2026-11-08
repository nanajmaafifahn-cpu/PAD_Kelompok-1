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
}
