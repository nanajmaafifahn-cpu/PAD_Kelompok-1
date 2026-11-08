{{-- Field form jadwal, dipakai schedules/index (tambah) dan schedules/edit (ubah).
     Variabel: $rooms collection Room, $schedule (opsional) Schedule yang diubah.
     Nama field = StoreScheduleRequest: room_id, title, start_time, end_time.
     Bentrok dengan jadwal lain / booking approved ditolak server dan tampil di bawah field start_time (D2, D8). --}}
@php
    $schedule = $schedule ?? null;
@endphp

@if ($rooms->isEmpty())
    <div role="alert" class="cs-alert cs-alert-warning mb-0">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
        <p>Belum ada ruang terdaftar. <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang</a> terlebih dahulu.</p>
    </div>
@endif

<div>
    <label for="room_id" class="cs-form-label">Ruang <span class="cs-form-required" aria-hidden="true">*</span></label>
    <select id="room_id" name="room_id" required class="cs-form-control"
            @error('room_id') aria-invalid="true" aria-describedby="room_id-error" @enderror>
        <option value="">— Pilih ruang —</option>
        @foreach ($rooms as $room)
            <option value="{{ $room->id }}" @selected(old('room_id', $schedule?->room_id) == $room->id)>{{ $room->code }} — {{ $room->name }}</option>
        @endforeach
    </select>
    <x-field-error for="room_id" />
</div>

<div>
    <label for="title" class="cs-form-label">Nama Kegiatan <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="title" name="title" type="text" value="{{ old('title', $schedule?->title) }}" required maxlength="150"
           pattern=".*\S.*" data-pattern-message="Nama kegiatan tidak boleh hanya berisi spasi."
           placeholder="Praktikum ALPRO" class="cs-form-control"
           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
    <x-field-error for="title" />
</div>

<div>
    <label for="start_time" class="cs-form-label">Mulai <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="start_time" name="start_time" type="datetime-local"
           value="{{ old('start_time', \App\Support\DateFormat::inputValue($schedule?->start_time)) }}" required
           class="cs-form-control"
           @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror>
    <x-field-error for="start_time" />
</div>

<div>
    <label for="end_time" class="cs-form-label">Selesai <span class="cs-form-required" aria-hidden="true">*</span></label>
    <input id="end_time" name="end_time" type="datetime-local"
           value="{{ old('end_time', \App\Support\DateFormat::inputValue($schedule?->end_time)) }}" required
           data-after="start_time" class="cs-form-control"
           aria-describedby="end_time-hint @error('end_time') end_time-error @enderror"
           @error('end_time') aria-invalid="true" @enderror>
    <p id="end_time-hint" class="cs-form-hint">Harus setelah waktu mulai.</p>
    <x-field-error for="end_time" />
</div>
