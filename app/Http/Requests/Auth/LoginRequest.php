<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form login. Hanya tamu (belum login) yang boleh mengirim form ini;
 * batas 5 percobaan/menit per email+IP diatur RateLimiter 'login'.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** @return array{email:string,password:string} */
    public function credentials(): array
    {
        return $this->safe()->only(['email', 'password']);
    }
}
