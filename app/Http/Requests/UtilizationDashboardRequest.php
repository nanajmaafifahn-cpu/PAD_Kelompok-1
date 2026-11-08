<?php

namespace App\Http\Requests;

use App\Support\Utilization\UtilizationPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input dashboard dan penyusunan periode, supaya
 * UtilizationDashboardController cukup menerima periode yang sudah jadi.
 */
class UtilizationDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Halaman HTML untuk semua user login; data JSON hanya pengelola, admin, pimpinan.
        return ! $this->expectsJson()
            || $this->user()->can('view-utilization-data');
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ];
    }

    /** Periode yang diminta; default minggu berjalan. Jam yang belum terjadi tidak dihitung. */
    public function period(): UtilizationPeriod
    {
        $start = $this->validated('start');
        $end = $this->validated('end');

        if ($start !== null && $end !== null) {
            return new UtilizationPeriod(
                CarbonImmutable::parse($start),
                CarbonImmutable::parse($end),
                CarbonImmutable::now()
            );
        }

        return UtilizationPeriod::currentWeek();
    }
}
