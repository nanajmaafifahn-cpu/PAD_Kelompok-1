<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter daftar ruang lewat query string (E4): q, type, min_capacity, facility.
 */
class RoomIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Room::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(array_keys(Room::TYPE_LABELS))],
            'min_capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'facility' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** @return array{q:?string,type:?string,min_capacity:?int,facility:?string} */
    public function filters(): array
    {
        return [
            'q' => $this->validated('q'),
            'type' => $this->validated('type'),
            'min_capacity' => $this->validated('min_capacity'),
            'facility' => $this->validated('facility'),
        ];
    }
}
