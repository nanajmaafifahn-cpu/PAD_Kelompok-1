<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** Ubah unit akademik; kode unik kecuali unit ini sendiri. */
class UpdateAcademicUnitRequest extends StoreAcademicUnitRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unit'));
    }

    protected function uniqueCodeRule(): Unique
    {
        return Rule::unique('academic_units', 'code')->ignore($this->route('unit'));
    }
}
