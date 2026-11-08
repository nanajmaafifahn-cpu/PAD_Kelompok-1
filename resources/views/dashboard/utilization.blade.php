{{-- Laporan Utilisasi (dashboard.utilization, pengelola/admin/pimpinan) — referensi 09-pengelola-laporan-utilisasi.
     Variabel dari UtilizationDashboardController@index (UtilizationDashboardService::build):
       $period         ['start', 'end', 'available_hours']
       $summary        ['total_rooms', 'underutilized', 'overutilized', 'occupied_now', 'booked_now', 'available_now',
                        'maintenance_now', 'total_scheduled_hours', 'total_actual_hours', 'total_ghost_bookings',
                        'average_utilization_rate', 'average_scheduled_rate', 'open_sessions']
       $rooms          array per ruang (room_code, room_name, room_type, scheduled_rate, utilization_rate, scheduled_hours,
                       actual_hours, approved_bookings, ghost_bookings, ghost_rate, category, metrics, live_status, ...)
       $underutilized  daftar kode ruang underutilized;  $overutilized  daftar kode ruang overutilized
     Grafik "Terjadwal vs aktual": dua bar per ruang (persen 0–100, satu sumbu). Warna seri divalidasi
     (skill dataviz): terjadwal #2a78d6, aktual #1baf7a; label persen selalu tampil + tabel rincian sebagai tampilan teks.
     Tombol "Cetak" saja (export CSV belum ada). Gaya @media print menyembunyikan sidebar/topbar/filter. --}}
@extends('layouts.app')

@use('App\Support\DateFormat')

@section('title', 'Laporan Utilisasi')
@section('inline_error_fields', 'start,end')

@php
    // Pengelompokan tampilan dari data Service (tanpa query dan tanpa rumus utilisasi baru).
    $roomList = collect($rooms);
    $hasRooms = $roomList->isNotEmpty();
    $avgUtilRate = $summary['average_utilization_rate'];
    $avgScheduledRate = $summary['average_scheduled_rate'];
    $totalGhosts = $summary['total_ghost_bookings'];
    $totalApproved = $roomList->sum('approved_bookings');
    $underRooms = $roomList->where('category', 'underutilized')->values();
    $overRooms = $roomList->where('category', 'overutilized')->values();
    $ghostRooms = $roomList->where('ghost_bookings', '>', 0)->sortByDesc('ghost_bookings')->values();
    $maintenanceRooms = $roomList->where('live_status', 'maintenance')->values();
    $roomRates = fn ($list) => $list->map(fn ($room) => $room['room_code'] . ' (' . $room['utilization_rate'] . '%)')->join(', ');

    // Konstanta aturan dari backend, agar teks penjelasan selalu sesuai kode.
    $openingHour = \App\Support\Utilization\UtilizationPeriod::OPENING_HOUR;
    $closingHour = \App\Support\Utilization\UtilizationPeriod::CLOSING_HOUR;
    $ghostTolerance = \App\Models\Booking::GHOST_TOLERANCE_MINUTES;

    $periodStart = \Carbon\CarbonImmutable::parse($period['start']);
    $periodEnd = \Carbon\CarbonImmutable::parse($period['end']);
    $isFiltered = request()->filled('start') || request()->filled('end');
@endphp

@section('page_header')
    <x-page-header eyebrow="Analitik & evaluasi" title="Bagaimana ruang digunakan?" crumb="Laporan Utilisasi"
                   subtitle="Bandingkan alokasi jadwal dengan penggunaan yang tercatat lewat check-in dan check-out.">
        <x-slot:actions>
            <button type="button" class="cs-btn cs-btn-primary" data-print hidden>
                <x-icon name="printer" class="h-4 w-4" /> Cetak
            </button>
        </x-slot:actions>
    </x-page-header>
    {{-- Identitas laporan saat dicetak (navigasi & filter tidak ikut tercetak) --}}
    <p class="cs-print-only mb-4 text-sm">
        Laporan Utilisasi · Periode {{ DateFormat::date($periodStart) }} s/d {{ DateFormat::date($periodEnd) }}
        · Dicetak {{ DateFormat::dateTime(now()) }}
    </p>
@endsection

@section('content')
    {{-- ===================== FILTER PERIODE ===================== --}}
    <x-filter-bar :action="route('dashboard.utilization')" :reset="route('dashboard.utilization')" :active="$isFiltered"
                  :cols="3" submit="Tampilkan Laporan" label="Filter periode laporan">
        <div>
            <label for="start_date" class="cs-form-label">Dari tanggal</label>
            <input type="date" id="start_date" name="start" value="{{ old('start', request('start', $period['start'])) }}" class="cs-form-control"
                   @error('start') aria-invalid="true" aria-describedby="start-error" @enderror>
            <x-field-error for="start" />
        </div>
        <div>
            <label for="end_date" class="cs-form-label">Sampai tanggal</label>
            <input type="date" id="end_date" name="end" value="{{ old('end', request('end', $period['end'])) }}" class="cs-form-control"
                   @error('end') aria-invalid="true" aria-describedby="end-error" @enderror>
            <x-field-error for="end" />
        </div>
        <p class="text-sm text-ink-soft md:self-center">
            Diperbarui saat halaman dibuka · WIB<br>
            <span class="text-xs text-ink-muted">Jam tersedia per ruang: {{ round($period['available_hours'], 1) }} jam</span>
        </p>
    </x-filter-bar>

    {{-- ===================== RINGKASAN (BE-09, dari summary) ===================== --}}
    <section aria-labelledby="kpi-heading" class="mb-8">
        <h2 id="kpi-heading" class="sr-only">Ringkasan periode {{ DateFormat::date($periodStart) }} s/d {{ DateFormat::date($periodEnd) }}</h2>
        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Jam terjadwal" :value="number_format($summary['total_scheduled_hours'], 1, ',', '.')"
                         caption="Dalam jam operasional yang sudah berlalu" icon="calendar" tone="scheduled" />
            <x-stat-card label="Jam aktual" :value="number_format($summary['total_actual_hours'], 1, ',', '.')"
                         caption="Dari check-in sampai check-out" icon="activity" tone="completed" />
            <x-stat-card label="Tidak digunakan" :value="$summary['total_ghost_bookings']"
                         caption="Peminjaman disetujui tanpa check-in (ghost)" icon="alert" tone="no_show" />
            <x-stat-card label="Sesi belum lengkap" :value="$summary['open_sessions']"
                         caption="Sudah check-in, belum check-out" icon="clock" tone="pending" />
        </dl>
    </section>

    {{-- ===================== GRAFIK TERJADWAL VS AKTUAL ===================== --}}
    <section aria-labelledby="chart-heading" class="cs-card cs-card-body mb-8">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="chart-heading" class="text-xl font-extrabold text-ink">Terjadwal vs aktual</h2>
                <p class="text-sm text-ink-soft">Persentase dari jam tersedia per ruang. Rata-rata: terjadwal {{ $avgScheduledRate }}% · aktual {{ $avgUtilRate }}%.</p>
            </div>
            <ul class="flex flex-wrap gap-4 text-sm text-ink-soft" aria-label="Keterangan grafik">
                <li class="cs-chart-legend" data-series="scheduled">Terjadwal</li>
                <li class="cs-chart-legend" data-series="actual">Aktual</li>
            </ul>
        </div>

        @if ($hasRooms)
            <ul class="cs-chart" aria-label="Persentase terjadwal dan aktual per ruang">
                @foreach ($roomList as $room)
                    <li class="cs-chart-row">
                        <span class="cs-chart-label" title="{{ $room['room_name'] }}">{{ $room['room_name'] }}</span>
                        <span class="cs-chart-bars">
                            <span class="cs-chart-bar" data-series="scheduled" style="--value: {{ min(100, $room['scheduled_rate']) }}%"
                                  title="{{ $room['room_name'] }} · terjadwal {{ $room['scheduled_rate'] }}% ({{ $room['scheduled_hours'] }} jam)"></span>
                            <span class="cs-chart-bar" data-series="actual" style="--value: {{ min(100, $room['utilization_rate']) }}%"
                                  title="{{ $room['room_name'] }} · aktual {{ $room['utilization_rate'] }}% ({{ $room['actual_hours'] }} jam)"></span>
                        </span>
                        <span class="cs-chart-value">
                            {{ $room['scheduled_rate'] }}% / {{ $room['utilization_rate'] }}%
                            <span class="sr-only">(terjadwal / aktual)</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-xs text-ink-muted">Angka lengkap per ruang ada di tabel "Rincian Utilisasi per Ruang" di bawah.</p>
        @else
            <x-empty-state title="Belum ada ruangan" text="Grafik muncul setelah data ruang ditambahkan." />
        @endif
    </section>

    {{-- ===================== UNDER / OVER UTILIZED ===================== --}}
    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="under-heading" class="cs-card cs-card-body">
            <h2 id="under-heading" class="cs-section-title">Ruang kurang dimanfaatkan</h2>
            <p class="cs-section-description">Utilisasi aktual di bawah 30% pada periode terpilih.</p>
            @if ($underRooms->isEmpty())
                <p class="cs-caption mt-4">Tidak ada ruang yang kurang dimanfaatkan pada periode ini.</p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($underRooms as $room)
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="cs-code">{{ $room['room_code'] }}</span>
                                <span class="font-medium text-ink">{{ $room['room_name'] }}</span>
                            </span>
                            <span class="whitespace-nowrap text-xs tabular-nums text-ink-soft">
                                aktual <strong class="text-warning">{{ $room['utilization_rate'] }}%</strong>
                                · terjadwal {{ $room['scheduled_rate'] }}%
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="over-heading" class="cs-card cs-card-body">
            <h2 id="over-heading" class="cs-section-title">Ruang sangat padat</h2>
            <p class="cs-section-description">Utilisasi aktual 80% atau lebih pada periode terpilih.</p>
            @if ($overRooms->isEmpty())
                <p class="cs-caption mt-4">Tidak ada ruang yang sangat padat pada periode ini.</p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($overRooms as $room)
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="cs-code">{{ $room['room_code'] }}</span>
                                <span class="font-medium text-ink">{{ $room['room_name'] }}</span>
                            </span>
                            <span class="whitespace-nowrap text-xs tabular-nums text-ink-soft">
                                aktual <strong class="text-danger">{{ $room['utilization_rate'] }}%</strong>
                                · terjadwal {{ $room['scheduled_rate'] }}%
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- ===================== REKOMENDASI KEPUTUSAN ===================== --}}
    <section aria-labelledby="recommendation-heading" class="mb-8">
        <h2 id="recommendation-heading" class="cs-section-title">Rekomendasi Keputusan</h2>
        <p class="cs-section-description">
            Disusun dari kategori utilisasi, peminjaman tidak hadir, dan status ruang pada periode terpilih.
            Bersifat pendukung keputusan; keputusan akhir tetap pada pimpinan fakultas. Keputusan permanen
            (mis. alih fungsi ruang) sebaiknya didasarkan pada perbandingan beberapa periode, bukan satu periode.
        </p>

        @if (! $hasRooms)
            <div class="cs-card mt-4">
                <div class="cs-empty-state">
                    <p class="cs-empty-state-title">Belum ada data ruang untuk dianalisis.</p>
                    <p class="mt-1">Rekomendasi muncul setelah ruang, jadwal, dan peminjaman tercatat.
                        <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang</a>.</p>
                </div>
            </div>
        @else
            <ol class="mt-4 grid gap-4 md:grid-cols-2">
                @if ($overRooms->isNotEmpty() && $underRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Redistribusi beban ruang (load balancing)</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                Pemakaian tidak merata: {{ $roomRates($overRooms) }} berada di atas 80%, sementara
                                {{ $roomRates($underRooms) }} di bawah 30%. Evaluasi pemindahan sebagian jadwal/peminjaman dari ruang
                                padat ke ruang yang kurang dimanfaatkan dengan kapasitas dan tipe ruang yang sesuai.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori sangat padat / kurang dimanfaatkan.</p>
                        </article>
                    </li>
                @elseif ($overRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Pemerataan jadwal &amp; kapasitas</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $roomRates($overRooms) }} berada pada utilisasi 80% atau lebih. Evaluasi pemerataan jadwal ke
                                jam/hari yang lebih longgar dan kecukupan kapasitas ruang sejenis sebagai alternatif.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori sangat padat.</p>
                        </article>
                    </li>
                @elseif ($underRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Redistribusi pemakaian ruang</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $roomRates($underRooms) }} berada di bawah 30%. Evaluasi redistribusi pemakaian: alihkan kegiatan
                                dari ruang lain, buka untuk peminjaman unit akademik lain, atau tinjau alih fungsi bila rendah berulang.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori kurang dimanfaatkan.</p>
                        </article>
                    </li>
                @endif

                @if ($ghostRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Evaluasi prosedur peminjaman &amp; check-in</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $totalGhosts }} dari {{ $totalApproved }} peminjaman disetujui berakhir tidak hadir:
                                {{ $ghostRooms->map(fn ($room) => $room['room_code'] . ' (' . $room['ghost_bookings'] . ')')->join(', ') }}.
                                Evaluasi konfirmasi ulang menjelang jadwal, pelepasan slot setelah toleransi {{ $ghostTolerance }} menit,
                                dan aturan bagi peminjam yang berulang kali tidak hadir.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: peminjaman tidak hadir &amp; peminjaman disetujui per ruang.</p>
                        </article>
                    </li>
                @endif

                @if ($maintenanceRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Pemulihan kapasitas ruang</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $maintenanceRooms->pluck('room_code')->join(', ') }} sedang dalam perawatan dan tidak dapat dipinjam.
                                Prioritaskan perbaikan agar kapasitas kembali tersedia.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: status ruang dalam perawatan.</p>
                        </article>
                    </li>
                @endif

                @if ($overRooms->isEmpty() && $underRooms->isEmpty() && $ghostRooms->isEmpty() && $maintenanceRooms->isEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Pertahankan pola jadwal</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                Semua ruang berada pada kategori normal, tanpa peminjaman tidak hadir dan tanpa ruang dalam perawatan.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: kategori utilisasi, peminjaman tidak hadir, status ruang saat ini.</p>
                        </article>
                    </li>
                @endif

                {{-- Konflik jadwal: datanya tidak tersedia, jadi tidak ada kesimpulan yang ditarik (usulan BE-12) --}}
                <li class="cs-card cs-card-body">
                    <article>
                        <h3 class="font-semibold text-ink">Pengaturan jadwal (konflik): belum dapat dinilai</h3>
                        <p class="mt-1 text-sm text-ink-soft">
                            Pengajuan yang bentrok ditolak sistem tetapi jumlahnya tidak dicatat, sehingga tekanan permintaan
                            pada slot tertentu belum terukur. Evaluasi pengaturan jadwal memerlukan pencatatan penolakan karena bentrok.
                        </p>
                        <p class="cs-caption mt-2">Dasar data: tidak tersedia.</p>
                    </article>
                </li>
            </ol>
        @endif
    </section>

    {{-- ===================== RINCIAN PER RUANG ===================== --}}
    <section aria-labelledby="detail-heading" class="cs-print-break-before mb-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 id="detail-heading" class="cs-section-title">Rincian Utilisasi per Ruang</h2>
            <ul class="cs-caption flex items-center gap-3" aria-label="Keterangan warna bar">
                <li class="flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-secondary" aria-hidden="true"></span>Terjadwal
                </li>
                <li class="flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-primary" aria-hidden="true"></span>Aktual (utilisasi)
                </li>
            </ul>
        </div>

        <div class="cs-table-wrap" role="region" aria-labelledby="detail-heading" tabindex="0">
            <table class="cs-table">
                <caption class="sr-only">Utilisasi terjadwal, aktual, peminjaman tidak hadir, dan kategori setiap ruang pada periode terpilih</caption>
                <thead>
                    <tr>
                        <th scope="col">Ruangan &amp; Tipe</th>
                        <th scope="col">Status sekarang</th>
                        <th scope="col">Terjadwal</th>
                        <th scope="col">Aktual &amp; Utilisasi</th>
                        <th scope="col" class="text-right">Tidak hadir</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Metrik Khusus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            {{-- Ruangan & Tipe --}}
                            <th scope="row" class="min-w-56">
                                <span class="flex items-center gap-2">
                                    <span class="cs-code shrink-0 whitespace-nowrap rounded border border-border bg-background px-1.5 py-0.5 font-semibold">{{ $room['room_code'] }}</span>
                                    <span class="font-medium text-ink">{{ $room['room_name'] }}</span>
                                </span>
                                <span class="mt-1 flex items-center gap-2 text-xs text-ink-muted">
                                    <span class="font-medium capitalize text-ink-soft">{{ $room['room_type'] }}</span>
                                    @if (! empty($room['academic_unit']))
                                        <span aria-hidden="true">&bull;</span>
                                        <span>{{ $room['academic_unit'] }}</span>
                                    @endif
                                </span>
                            </th>

                            {{-- Status Live --}}
                            <td class="cs-cell-nowrap">
                                <x-status-badge :status="$room['live_status']" />
                            </td>

                            {{-- Terjadwal --}}
                            <td class="w-44">
                                <p class="flex flex-wrap items-baseline justify-between gap-x-2 text-xs">
                                    <span class="font-semibold tabular-nums text-ink">{{ $room['scheduled_rate'] }}%</span>
                                    <span class="tabular-nums text-ink-muted">{{ $room['scheduled_hours'] }} jam</span>
                                </p>
                                <div class="mt-1.5">
                                    <x-progress-bar :value="$room['scheduled_rate']" color="bg-secondary" :label="'Utilisasi terjadwal ' . $room['room_code']" />
                                </div>
                            </td>

                            {{-- Aktual & Utilisasi --}}
                            <td class="w-44">
                                <p class="flex flex-wrap items-baseline justify-between gap-x-2 text-xs">
                                    <span class="font-semibold tabular-nums text-ink">{{ $room['utilization_rate'] }}%</span>
                                    <span class="tabular-nums text-ink-muted">{{ $room['actual_hours'] }} jam</span>
                                </p>
                                <div class="mt-1.5">
                                    <x-progress-bar :value="$room['utilization_rate']" :label="'Utilisasi aktual ' . $room['room_code']" />
                                </div>
                            </td>

                            {{-- Ghost Booking --}}
                            <td class="cs-cell-num">
                                <span @class(['font-semibold', 'text-danger font-bold' => $room['ghost_bookings'] > 0, 'text-ink-body' => $room['ghost_bookings'] === 0])>
                                    {{ $room['ghost_bookings'] }}
                                </span>
                                <span class="text-xs text-ink-muted">/ {{ $room['approved_bookings'] }}</span>
                                <span class="block text-[11px] text-ink-muted">{{ $room['ghost_rate'] }}% tidak hadir</span>
                            </td>

                            {{-- Kategori --}}
                            <td class="cs-cell-nowrap">
                                <x-status-badge :status="$room['category']" />
                            </td>

                            {{-- Metrik Khusus (Strategy) --}}
                            <td class="text-xs text-ink-soft">
                                @if (! empty($room['metrics']))
                                    <dl class="space-y-1">
                                        @foreach ($room['metrics'] as $key => $val)
                                            <div class="flex items-center justify-between gap-2 border-b border-border pb-0.5 last:border-0 last:pb-0">
                                                <dt class="capitalize text-ink-muted">{{ str_replace('_', ' ', $key) }}:</dt>
                                                <dd class="font-medium tabular-nums text-ink-body">
                                                    @if (is_numeric($val))
                                                        {{ str_contains($key, 'ratio') || str_contains($key, 'rate') ? $val . '%' : (str_contains($key, 'hours') ? $val . ' jam' : $val) }}
                                                    @else
                                                        {{ $val ?? '-' }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @else
                                    <span class="text-ink-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="cs-empty-state">
                                    <p class="cs-empty-state-title">Belum ada data ruang atau aktivitas pada periode ini.</p>
                                    <p class="mt-1">Data utilisasi dihitung dari ruang, jadwal, dan peminjaman yang tercatat.
                                        <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang baru</a>.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ===================== CATATAN METODOLOGI ===================== --}}
    <aside aria-labelledby="method-heading" class="cs-alert cs-alert-info">
        <span class="cs-alert-icon" aria-hidden="true">i</span>
        <div>
            <h2 id="method-heading" class="cs-alert-title">Cara membaca angka</h2>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                <li>Jam tersedia = jam operasional {{ sprintf('%02d.00', $openingHour) }}–{{ sprintf('%02d.00', $closingHour) }}, Senin–Jumat; jam yang belum terjadi tidak dihitung.</li>
                <li>Terjadwal = jadwal resmi + peminjaman disetujui. Aktual = durasi check-in sampai check-out; rumus laboratorium ikut memperhitungkan waktu setup dan penalti peminjaman tidak hadir.</li>
                <li>Tidak hadir (<i>ghost booking</i>) = peminjaman disetujui yang tidak check-in lebih dari {{ $ghostTolerance }} menit setelah jadwal mulai.</li>
                <li>Konflik jadwal dicegah saat pengajuan (pengajuan yang bentrok ditolak), sehingga tidak muncul sebagai metrik di dashboard.</li>
            </ul>
        </div>
    </aside>
@endsection
