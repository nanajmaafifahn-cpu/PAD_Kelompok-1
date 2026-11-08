<?php

namespace App\Http\Requests;

use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah fasilitas ke satu ruang (nama, jumlah, kondisi). Nama unik per ruang.
 */
class StoreFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Facility::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->uniqueNameRule()],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(array_keys(Facility::STATUS_LABELS))],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Fasilitas dengan nama ini sudah ada di ruang tersebut.'];
    }

    protected function uniqueNameRule(): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique('facilities', 'name')->where('room_id', $this->route('room')->id);
    }
}
