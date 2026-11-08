<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** Ubah fasilitas; nama unik per ruang kecuali fasilitas ini sendiri. */
class UpdateFacilityRequest extends StoreFacilityRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('facility'));
    }

    protected function uniqueNameRule(): Unique
    {
        $facility = $this->route('facility');

        return Rule::unique('facilities', 'name')
            ->where('room_id', $facility->room_id)
            ->ignore($facility);
    }
}
