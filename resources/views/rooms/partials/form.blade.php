{{-- Field form ruang, dipakai bersama oleh rooms/create (tambah) dan rooms/edit (ubah).
     Variabel:
       $academicUnits  collection AcademicUnit
       $room           (opsional) Room yang diubah; nilai awal = old() lalu fallback ke data model.
     Nama field = key StoreRoomRequest: academic_unit_id, code, name, building, floor, capacity, status, description.
     Atribut required/min/maxlength hanya validasi dasar di browser; validasi utama tetap di server.
     Field `type` belum didukung backend (usulan BE-04), jadi tidak ditampilkan.
     Halaman pemakai wajib mendaftarkan @section('inline_error_fields', ...) dengan nama field di atas. --}}
@php
    $room = $room ?? null;
    $currentStatus = old('status', $room?->getStatus() ?? 'available');
@endphp

@if ($academicUnits->isEmpty())
    <div role="alert" class="cs-alert cs-alert-warning">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
        <p>Belum ada data unit akademik, sehingga ruang belum dapat disimpan. Jalankan seeder database terlebih dahulu.</p>
    </div>
@endif

{{-- Identitas ruang --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Identitas Ruang</legend>
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="academic_unit_id" class="cs-form-label">
                Unit Akademik <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <select id="academic_unit_id" name="academic_unit_id" required class="cs-form-control"
                    @error('academic_unit_id') aria-invalid="true" aria-describedby="academic_unit_id-error" @enderror>
                <option value="">— Pilih unit akademik —</option>
                @foreach ($academicUnits as $unit)
                    <option value="{{ $unit->id }}" @selected(old('academic_unit_id', $room?->academic_unit_id) == $unit->id)>
                        {{ $unit->code }} — {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            <x-field-error for="academic_unit_id" />
        </div>

        <div>
            <label for="code" class="cs-form-label">
                Kode Ruang <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="code" name="code" type="text" value="{{ old('code', $room?->code) }}" required maxlength="30"
                   placeholder="C301" autocomplete="off" class="cs-form-control"
                   aria-describedby="code-hint @error('code') code-error @enderror"
                   @error('code') aria-invalid="true" @enderror>
            <p id="code-hint" class="cs-form-hint">Unik untuk setiap ruang, maksimal 30 karakter.</p>
            <x-field-error for="code" />
        </div>

        <div>
            <label for="name" class="cs-form-label">
                Nama Ruang <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="name" name="name" type="text" value="{{ old('name', $room?->name) }}" required maxlength="150"
                   placeholder="Laboratorium C301" class="cs-form-control"
                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            <x-field-error for="name" />
        </div>
    </div>
</fieldset>

{{-- Lokasi & kapasitas --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Lokasi &amp; Kapasitas</legend>
    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="building" class="cs-form-label">
                Gedung <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="building" name="building" type="text" value="{{ old('building', $room?->building) }}" required maxlength="100"
                   placeholder="Gedung C" class="cs-form-control"
                   @error('building') aria-invalid="true" aria-describedby="building-error" @enderror>
            <x-field-error for="building" />
        </div>

        <div>
            <label for="floor" class="cs-form-label">Lantai</label>
            <input id="floor" name="floor" type="text" value="{{ old('floor', $room?->floor) }}" maxlength="30"
                   placeholder="3" class="cs-form-control"
                   @error('floor') aria-invalid="true" aria-describedby="floor-error" @enderror>
            <x-field-error for="floor" />
        </div>

        <div>
            <label for="capacity" class="cs-form-label">
                Kapasitas (orang) <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="capacity" name="capacity" type="number" value="{{ old('capacity', $room?->getCapacity()) }}" required min="1" step="1"
                   inputmode="numeric" placeholder="30" class="cs-form-control"
                   @error('capacity') aria-invalid="true" aria-describedby="capacity-error" @enderror>
            <x-field-error for="capacity" />
        </div>
    </div>
</fieldset>

{{-- Status & keterangan --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Status &amp; Keterangan</legend>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="status" class="cs-form-label">
                {{ $room ? 'Status' : 'Status Awal' }} <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <select id="status" name="status" required class="cs-form-control"
                    aria-describedby="status-hint @error('status') status-error @enderror"
                    @error('status') aria-invalid="true" @enderror>
                <option value="available" @selected($currentStatus === 'available')>Available</option>
                <option value="occupied" @selected($currentStatus === 'occupied')>Occupied</option>
                <option value="maintenance" @selected($currentStatus === 'maintenance')>Maintenance</option>
            </select>
            <p id="status-hint" class="cs-form-hint">Ruang berstatus Maintenance tidak dapat dibooking.</p>
            <x-field-error for="status" />
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="cs-form-label">Deskripsi</label>
            <textarea id="description" name="description" rows="3" placeholder="Contoh: Lab komputer, 30 PC, proyektor"
                      class="cs-form-control"
                      @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $room?->description) }}</textarea>
            <x-field-error for="description" />
        </div>
    </div>
</fieldset>
