{{-- Field form booking, dipakai bersama oleh bookings/create (ajukan) dan bookings/edit (ubah).
     Variabel:
       $rooms     collection Room (data ruang dari backend; option membawa data-capacity untuk cek kapasitas)
       $booking   (opsional) Booking yang diubah; nilai awal = old() lalu fallback ke data model.
       $selectedRoomId (opsional, bookings.create?room_id=..) ruang yang langsung terpilih.
     Data pemohon (nama & peran) tidak diinput: diambil dari akun login oleh BookingService (B2).
     Nama field = key StoreBookingRequest:
       room_id, participant_count, start_time, end_time, purpose.
     Validasi HTML5 + campus-space.js hanya umpan balik awal; validasi utama tetap di server
     (FormRequest + BookingService: ruang maintenance, kapasitas, bentrok jadwal).
     Halaman pemakai wajib mendaftarkan @section('inline_error_fields', ...) dengan nama field di atas. --}}
@php
    $booking = $booking ?? null;
    $selectedRoom = old('room_id', $booking?->room_id ?? ($selectedRoomId ?? null));
@endphp

@if ($rooms->isEmpty())
    <div role="alert" class="cs-alert cs-alert-warning">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
        <p>Belum ada ruang terdaftar, sehingga peminjaman belum dapat diajukan.
            <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang</a> terlebih dahulu.</p>
    </div>
@endif

{{-- Data pemohon --}}
<fieldset class="cs-fieldset">
    <legend class="cs-legend">Data Pemohon</legend>
    <dl class="grid gap-5 sm:grid-cols-2">
        <div>
            <dt class="cs-form-label">Nama Pemohon</dt>
            <dd class="text-ink">{{ $booking?->requester_name ?? auth()->user()->name }}</dd>
        </div>

        <div>
            <dt class="cs-form-label">Peran</dt>
            <dd class="text-ink">{{ $booking?->requester_role ?? auth()->user()->roleLabel() }}</dd>
        </div>
    </dl>
    <p class="cs-form-hint mt-2">Diambil otomatis dari akun yang sedang login.</p>
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
                   value="{{ old('start_time', \App\Support\DateFormat::inputValue($booking?->start_time)) }}" required
                   class="cs-form-control"
                   @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror>
            <x-field-error for="start_time" />
        </div>

        <div>
            <label for="end_time" class="cs-form-label">
                Waktu Selesai <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <input id="end_time" name="end_time" type="datetime-local"
                   value="{{ old('end_time', \App\Support\DateFormat::inputValue($booking?->end_time)) }}" required
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
    <p>Sistem akan menolak pengajuan bila ruang sedang perawatan, jadwal bentrok dengan peminjaman/jadwal lain
        di ruang yang sama, atau jumlah peserta melebihi kapasitas ruang.</p>
</div>
