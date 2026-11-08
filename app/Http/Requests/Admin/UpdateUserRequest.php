<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Ubah akun oleh admin: nama, email, role, status aktif, dan (opsional) password baru.
 * Admin tidak boleh menurunkan role atau menonaktifkan dirinya sendiri.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->route('user')->is($this->user())) {
                    return;
                }

                if ($this->input('role') !== User::ROLE_ADMIN) {
                    $validator->errors()->add('role', 'Anda tidak dapat menurunkan role akun Anda sendiri.');
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
                }
            },
        ];
    }

    /** Data siap simpan: password hanya diganti bila diisi. */
    public function userData(): array
    {
        $data = $this->safe()->only(['name', 'email', 'role']);
        $data['is_active'] = $this->boolean('is_active');

        if (filled($this->validated('password'))) {
            $data['password'] = $this->validated('password');
        }

        return $data;
    }
}
