{{-- Field unit akademik: code, name, description. $unit opsional (ubah). --}}
<div>
    <label for="code" class="cs-form-label">Kode <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="code" name="code" type="text" value="{{ old('code', $unit?->code) }}" required maxlength="30" placeholder="FRI"
           class="cs-form-control" @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
    <x-field-error for="code" />
</div>
<div>
    <label for="name" class="cs-form-label">Nama <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="name" name="name" type="text" value="{{ old('name', $unit?->name) }}" required maxlength="150"
           placeholder="Fakultas Rekayasa Industri" class="cs-form-control"
           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    <x-field-error for="name" />
</div>
<div>
    <label for="description" class="cs-form-label">Deskripsi</label>
    <textarea id="description" name="description" rows="2" maxlength="2000" class="cs-form-control"
              @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $unit?->description) }}</textarea>
    <x-field-error for="description" />
</div>
