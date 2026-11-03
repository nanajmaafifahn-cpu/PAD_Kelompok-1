{{-- FE-03 / FE-05 · Jadwal Ruang (dasar perhitungan Scheduled Utilization)
     Variabel dari ScheduleController@index:
       $schedules  paginator Schedule (dengan relasi room), urut waktu mulai
       $rooms      (opsional) collection Room untuk form tambah jadwal.
                   BLOCKED: ScheduleController@index belum mengirim $rooms (usulan BE-03), jadi form
                   tidak ditampilkan dan diganti keterangan. Endpoint schedules.store sudah tersedia;
                   begitu $rooms dikirim, form di bawah langsung tampil tanpa mengubah view ini.
     Nama field form = key StoreScheduleRequest: room_id, title, start_time, end_time (status otomatis "scheduled").
     Di bawah 768px tabel tampil sebagai kartu (.cs-table-stack). --}}
@extends('layouts.app')

@section('title', 'Jadwal Ruang')
@section('inline_error_fields', 'room_id,title,start_time,end_time')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Jadwal Ruang</h1>
            <p class="cs-page-description">Jadwal resmi perkuliahan/praktikum yang menjadi acuan <em>scheduled utilization</em>.</p>
        </div>
    </header>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        {{-- Daftar jadwal --}}
        <section aria-labelledby="schedules-heading" class="min-w-0 flex-1">
            <h2 id="schedules-heading" class="sr-only">Daftar jadwal</h2>

            <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-labelledby="schedules-heading" tabindex="0">
                <table class="cs-table cs-table-stack">
                    <caption class="sr-only">
                        Daftar jadwal ruang urut waktu mulai, halaman {{ $schedules->currentPage() }} dari {{ $schedules->lastPage() }}
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Kegiatan</th>
                            <th scope="col">Ruang</th>
                            <th scope="col">Hari &amp; Tanggal</th>
                            <th scope="col">Waktu</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $schedule)
                            <tr>
                                <th scope="row"><span class="font-medium text-ink">{{ $schedule->title }}</span></th>
                                <td data-label="Ruang">
                                    <span class="block text-ink">{{ $schedule->room?->name }}</span>
                                    <span class="cs-code">{{ $schedule->room?->code }}</span>
                                </td>
                                <td data-label="Hari & Tanggal" class="cs-cell-nowrap">
                                    <time datetime="{{ $schedule->start_time->toDateString() }}">
                                        {{ $schedule->start_time->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                                    </time>
                                </td>
                                <td data-label="Waktu" class="cs-cell-nowrap tabular-nums">
                                    <time datetime="{{ $schedule->start_time->toIso8601String() }}">{{ $schedule->start_time->format('H:i') }}</time>
                                    –
                                    <time datetime="{{ $schedule->end_time->toIso8601String() }}">{{ $schedule->end_time->format('H:i') }}</time>
                                    @unless ($schedule->start_time->isSameDay($schedule->end_time))
                                        <span class="block text-xs text-ink-muted">s/d {{ $schedule->end_time->locale('id')->isoFormat('D MMM YYYY') }}</span>
                                    @endunless
                                </td>
                                <td data-label="Status"><x-status-badge :status="$schedule->status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="cs-empty-state">
                                        <p class="cs-empty-state-title">Belum ada jadwal.</p>
                                        <p class="mt-1">Jadwal resmi perkuliahan dan praktikum akan muncul di sini dan dihitung sebagai jam terjadwal ruang.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
        </section>

        {{-- Sidebar: form tambah jadwal (hanya bila data ruang dikirim Controller) --}}
        <aside aria-labelledby="schedule-form-heading" class="w-full shrink-0 lg:w-80">
            @isset($rooms)
                <form method="POST" action="{{ route('schedules.store') }}" class="cs-card cs-card-body space-y-4" data-cs-validate>
                    @csrf
                    <h2 id="schedule-form-heading" class="cs-section-title">Tambah Jadwal</h2>

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
                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->code }} — {{ $room->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error for="room_id" />
                    </div>

                    <div>
                        <label for="title" class="cs-form-label">Nama Kegiatan <span class="cs-form-required" aria-hidden="true">*</span></label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="150"
                               pattern=".*\S.*" data-pattern-message="Nama kegiatan tidak boleh hanya berisi spasi."
                               placeholder="Praktikum ALPRO" class="cs-form-control"
                               @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                        <x-field-error for="title" />
                    </div>

                    <div>
                        <label for="start_time" class="cs-form-label">Mulai <span class="cs-form-required" aria-hidden="true">*</span></label>
                        <input id="start_time" name="start_time" type="datetime-local" value="{{ old('start_time') }}" required
                               class="cs-form-control"
                               @error('start_time') aria-invalid="true" aria-describedby="start_time-error" @enderror>
                        <x-field-error for="start_time" />
                    </div>

                    <div>
                        <label for="end_time" class="cs-form-label">Selesai <span class="cs-form-required" aria-hidden="true">*</span></label>
                        <input id="end_time" name="end_time" type="datetime-local" value="{{ old('end_time') }}" required
                               data-after="start_time" class="cs-form-control"
                               aria-describedby="end_time-hint @error('end_time') end_time-error @enderror"
                               @error('end_time') aria-invalid="true" @enderror>
                        <p id="end_time-hint" class="cs-form-hint">Harus setelah waktu mulai.</p>
                        <x-field-error for="end_time" />
                    </div>

                    <button type="submit" class="cs-btn cs-btn-primary w-full">Simpan Jadwal</button>
                </form>
            @else
                <div class="cs-card cs-card-body">
                    <h2 id="schedule-form-heading" class="cs-section-title">Tambah Jadwal</h2>
                    <p class="mt-2 text-sm text-ink-soft">
                        Form tambah jadwal belum dapat ditampilkan karena daftar ruang belum disediakan oleh sistem.
                    </p>
                    <p class="cs-caption mt-2">Jadwal yang sudah tercatat tetap tampil dan dihitung di dashboard utilisasi.</p>
                </div>
            @endisset
        </aside>
    </div>
@endsection
