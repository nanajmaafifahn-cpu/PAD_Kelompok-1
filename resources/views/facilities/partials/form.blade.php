{{-- Field form fasilitas (dipakai facilities/index & facilities/edit).
     Variabel: $statuses (Facility::STATUS_LABELS), $facility (opsional, Facility yang diubah).
     Nama field = StoreFacilityRequest: name, quantity, status. --}}
<div>
    <label for="name" class="cs-form-label">Nama <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="name" name="name" type="text" value="{{ old('name', $facility?->name) }}" required maxlength="150"
           placeholder="PC Core i5 RAM 16 GB" class="cs-form-control"
           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    <x-field-error for="name" />
</div>

<div>
    <label for="quantity" class="cs-form-label">Jumlah <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="quantity" name="quantity" type="number" min="0" step="1" inputmode="numeric"
           value="{{ old('quantity', $facility?->getQuantity()) }}" required class="cs-form-control"
           @error('quantity') aria-invalid="true" aria-describedby="quantity-error" @enderror>
    <x-field-error for="quantity" />
</div>

<div>
    <label for="status" class="cs-form-label">Kondisi <span class="cs-form-required" aria-hidden="true">*</span></label>
    <select id="status" name="status" required class="cs-form-control"
            @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $facility?->getStatus() ?? 'available') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-field-error for="status" />
</div>
