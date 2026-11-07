<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** Tambah unit akademik (admin): kode unik, nama, deskripsi. */
class StoreAcademicUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AcademicUnit::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', $this->uniqueCodeRule()],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function uniqueCodeRule(): Unique
    {
        return Rule::unique('academic_units', 'code');
    }
}
