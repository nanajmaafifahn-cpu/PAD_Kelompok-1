<?php

namespace App\Http\Requests;

/** Ubah jadwal: field sama dengan StoreScheduleRequest, khusus pengelola/admin. */
class UpdateScheduleRequest extends StoreScheduleRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('schedule'));
    }
}
