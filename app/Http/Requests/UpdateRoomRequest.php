<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Ubah data ruang: field sama dengan StoreRoomRequest, kode unik kecuali ruang ini sendiri.
 * Centang `remove_photo` untuk menghapus foto tanpa mengganti.
 */
class UpdateRoomRequest extends StoreRoomRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('room'));
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    protected function uniqueCodeRule(): Unique
    {
        return Rule::unique('rooms', 'code')->ignore($this->route('room'));
    }
}
