{{-- Field form booking, dipakai bersama oleh bookings/create (ajukan) dan bookings/edit (ubah).
     Variabel:
       $rooms     collection Room (data ruang dari backend; option membawa data-capacity untuk cek kapasitas)
       $roles     array label role stakeholder
       $booking   (opsional) Booking yang diubah; nilai awal = old() lalu fallback ke data model.
     Nama field = key StoreBookingRequest:
       requester_name, requester_role, room_id, participant_count, start_time, end_time, purpose.
     Validasi HTML5 + campus-space.js hanya umpan balik awal; validasi utama tetap di server
     (FormRequest + BookingService: ruang maintenance, kapasitas, bentrok jadwal).
     Halaman pemakai wajib mendaftarkan @section('inline_error_fields', ...) dengan nama field di atas. --}}
@php
    $booking = $booking ?? null;
    $selectedRole = old('requester_role', $booking?->requester_role);
    $roleOptions = collect($roles)
        ->when($selectedRole && ! in_array($selectedRole, $roles, true), fn ($options) => $options->push($selectedRole));
    $selectedRoom = old('room_id', $booking?->room_id);
@endphp

@if ($rooms->isEmpty())
    <div role="alert" class="cs-alert cs-alert-warning">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
        <p>Belum ada ruang terdaftar, sehingga booking belum dapat diajukan.
            <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang</a> terlebih dahulu.</p>
    </div>
@endif

{{-- Data pemohon --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Data Pemohon</legend>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="requester_name" class="cs-form-label">
                Nama Pemohon <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="requester_name" name="requester_name" type="text" value="{{ old('requester_name', $booking?->requester_name) }}"
                   required maxlength="150" pattern=".*\S.*" autocomplete="name"
                   data-pattern-message="Nama tidak boleh hanya berisi spasi."
                   class="cs-form-control"
                   @error('requester_name') aria-invalid="true" aria-describedby="requester_name-error" @enderror>
            <x-field-error for="requester_name" />
        </div>

        <div>
            <label for="requester_role" class="cs-form-label">
                Role Stakeholder <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <select id="requester_role" name="requester_role" required class="cs-form-control"
                    @error('requester_role') aria-invalid="true" aria-describedby="requester_role-error" @enderror>
                <option value="">— Pilih role —</option>
                @foreach ($roleOptions as $role)
                    <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <x-field-error for="requester_role" />
        </div>
    </div>
</fieldset>

{{-- Ruang & peserta --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Ruang &amp; Peserta</legend>
    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="room_id" class="cs-form-label">
                Ruang / Lab <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <select id="room_id" name="room_id" required class="cs-form-control"
                    aria-describedby="room_id-hint @error('room_id') room_id-error @enderror"
                    @error('room_id') aria-invalid="true" @enderror>
                <option value="">— Pilih ruang —</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" data-capacity="{{ $room->getCapacity() }}"
                            @selected($selectedRoom == $room->id)
                            @disabled($room->getStatus() === 'maintenance' && $selectedRoom != $room->id)>
                        {{ $room->code }} — {{ $room->name }} (kapasitas {{ $room->getCapacity() }}){{ $room->getStatus() === 'maintenance' ? ' · sedang perawatan' : '' }}
                    </option>
                @endforeach
            </select>
            <p id="room_id-hint" class="cs-form-hint">Ruang yang sedang perawatan tidak dapat dipilih.</p>
            <x-field-error for="room_id" />
        </div>

        <div>
            <label for="participant_count" class="cs-form-label">
                Jumlah Peserta <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="participant_count" name="participant_count" type="number" value="{{ old('participant_count', $booking?->participant_count) }}"
                   required min="1" step="1" inputmode="numeric" data-capacity-from="room_id"
                   class="cs-form-control"
                   aria-describedby="participant_count-capacity @error('participant_count') participant_count-error @enderror"
                   @error('participant_count') aria-invalid="true" @enderror>
            <p id="participant_count-capacity" class="cs-form-hint" aria-live="polite"
               data-default="Tidak boleh melebihi kapasitas ruang.">Tidak boleh melebihi kapasitas ruang.</p>
            <x-field-error for="participant_count" />
        </div>
    </div>
</fieldset>

{{-- Waktu --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Waktu Peminjaman</legend>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="start_time" class="cs-form-label">
                Waktu Mulai <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="start_time" name="start_time" type="datetime-local"
                   value="{{ old('start_time', $booking?->start_time?->format('Y-m-d\TH:i')) }}" required
                   class="cs-form-control"
                   @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror>
            <x-field-error for="start_time" />
        </div>

        <div>
            <label for="end_time" class="cs-form-label">
                Waktu Selesai <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="end_time" name="end_time" type="datetime-local"
                   value="{{ old('end_time', $booking?->end_time?->format('Y-m-d\TH:i')) }}" required
                   data-after="start_time" class="cs-form-control"
                   aria-describedby="end_time-hint @error('end_time') end_time-error @enderror"
                   @error('end_time') aria-invalid="true" @enderror>
            <p id="end_time-hint" class="cs-form-hint">Harus setelah waktu mulai.</p>
            <x-field-error for="end_time" />
        </div>
    </div>
</fieldset>

{{-- Keperluan --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Keperluan</legend>
    <label for="purpose" class="cs-form-label">
        Keperluan Peminjaman <span class="cs-form-required" aria-hidden="true">*</span>
    </label>
    <textarea id="purpose" name="purpose" rows="3" required maxlength="255" data-not-blank
              placeholder="Contoh: Praktikum Basis Data kelas A" class="cs-form-control"
              aria-describedby="purpose-hint @error('purpose') purpose-error @enderror"
              @error('purpose') aria-invalid="true" @enderror>{{ old('purpose', $booking?->purpose) }}</textarea>
    <p id="purpose-hint" class="cs-form-hint">Maksimal 255 karakter.</p>
    <x-field-error for="purpose" />
</fieldset>

<div class="cs-alert cs-alert-info mb-0">
    <span aria-hidden="true" class="cs-alert-icon">i</span>
    <p>Sistem akan menolak pengajuan bila ruang sedang perawatan, jadwal bentrok dengan booking/jadwal lain
        di ruang yang sama, atau jumlah peserta melebihi kapasitas ruang.</p>
</div>
