{{-- Field akun: name, email, role. $user opsional (ubah), $isSelf = admin sedang mengubah akunnya sendiri. --}}
<div>
    <label for="name" class="cs-form-label">Nama <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required maxlength="150" class="cs-form-control"
           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    <x-field-error for="name" />
</div>
<div>
    <label for="email" class="cs-form-label">Email <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required class="cs-form-control"
           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    <x-field-error for="email" />
</div>
<div>
    <label for="role" class="cs-form-label">Role <span class="cs-form-required" aria-hidden="true">*</span></label>
    <select id="role" name="role" required class="cs-form-control"
            aria-describedby="role-hint @error('role') role-error @enderror" @error('role') aria-invalid="true" @enderror>
        @foreach ($roles as $value => $label)
            <option value="{{ $value }}" @selected(old('role', $user?->role ?? \App\Models\User::ROLE_BORROWER) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($isSelf)
        <p id="role-hint" class="cs-form-hint">Anda tidak dapat menurunkan role akun Anda sendiri.</p>
    @endif
    <x-field-error for="role" />
</div>
