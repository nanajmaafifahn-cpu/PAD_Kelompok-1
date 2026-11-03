{{-- FE-04 · Live Utilization Dashboard
     Variabel dari UtilizationDashboardController@index (UtilizationDashboardService::build):
       $period         ['start', 'end', 'available_hours']
       $summary        ['total_rooms', 'underutilized', 'overutilized', 'occupied_now', 'booked_now', 'available_now']
       $rooms          array per ruang (room_code, room_name, room_type, scheduled_rate, utilization_rate,
                       approved_bookings, ghost_bookings, ghost_rate, category, metrics, live_status, academic_unit, ...)
       $underutilized  daftar kode ruang underutilized
       $overutilized   daftar kode ruang overutilized
     Struktur lengkap: docs/fe/KONTRAK-VIEW.md bagian 3. --}}
@extends('layouts.app')

@section('title', 'Dashboard Utilisasi Ruang & Lab')

@push('head')
    {{-- Refresh berkala untuk live update status ruang. Dengan JS, campus-space.js (data-auto-refresh)
         me-refresh tiap 30 detik tetapi bisa dijeda dan menunda refresh saat filter diisi / dialog cetak terbuka.
         Tanpa JS, meta refresh di bawah tetap berlaku. --}}
    <noscript><meta http-equiv="refresh" content="30"></noscript>
@endpush

@section('content')
    @php
        // Agregasi tampilan dari data Service (tanpa query dan tanpa rumus utilisasi baru).
        // Usulan BE-09: pindahkan agregasi ini ke UtilizationDashboardService.
        $roomList = collect($rooms);
        $hasRooms = $roomList->isNotEmpty();
        $totalRooms = $summary['total_rooms'] ?? $roomList->count();
        $avgUtilRate = $hasRooms ? round($roomList->avg('utilization_rate'), 1) : 0;
        $avgScheduledRate = $hasRooms ? round($roomList->avg('scheduled_rate'), 1) : 0;
        $totalApproved = $roomList->sum('approved_bookings');
        $totalGhosts = $roomList->sum('ghost_bookings');
        $normalCount = $roomList->where('category', 'normal')->count();
        $underRooms = $roomList->where('category', 'underutilized')->values();
        $overRooms = $roomList->where('category', 'overutilized')->values();
        $ghostRooms = $roomList->where('ghost_bookings', '>', 0)->sortByDesc('ghost_bookings')->values();
        $maintenanceRooms = $roomList->where('live_status', 'maintenance')->values();

        // FE-06 laporan: total jam = penjumlahan nilai per ruang dari Service (tanpa rumus baru).
        $totalAvailableHours = round($roomList->sum('available_hours'), 2);
        $totalScheduledHours = round($roomList->sum('scheduled_hours'), 2);
        $totalActualHours = round($roomList->sum('actual_hours'), 2);
        $roomRates = fn ($list) => $list->map(fn ($room) => $room['room_code'] . ' (' . $room['utilization_rate'] . '%)')->join(', ');

        // Konstanta aturan dari backend, agar teks penjelasan selalu sesuai kode.
        $openingHour = \App\Support\Utilization\UtilizationPeriod::OPENING_HOUR;
        $closingHour = \App\Support\Utilization\UtilizationPeriod::CLOSING_HOUR;
        $ghostTolerance = \App\Models\Booking::GHOST_TOLERANCE_MINUTES;
    @endphp

    {{-- ===================== HEADER HALAMAN ===================== --}}
    <header class="cs-page-header" data-auto-refresh="30">
        <div>
            <h1 class="cs-page-title">Dashboard &amp; Laporan Utilisasi Ruang &amp; Laboratorium</h1>
            <p class="cs-page-description">
                Pemantauan efisiensi pemanfaatan ruang, perbandingan jadwal vs aktual, serta deteksi <em>ghost booking</em>.
            </p>
            {{-- Identitas laporan saat dicetak (navigasi & filter tidak ikut tercetak) --}}
            <p class="cs-print-only mt-1 text-sm">
                Periode {{ \Carbon\Carbon::parse($period['start'])->locale('id')->isoFormat('D MMMM YYYY') }}
                s/d {{ \Carbon\Carbon::parse($period['end'])->locale('id')->isoFormat('D MMMM YYYY') }}
                · Dicetak {{ now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}
                · Sumber data: UtilizationDashboardService
            </p>
        </div>
        <div class="cs-no-print flex flex-wrap items-center gap-2 self-start sm:self-auto">
            <p class="cs-status-indicator" data-status="available" aria-live="polite">
                <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-available opacity-75 motion-safe:animate-ping"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-available"></span>
                </span>
                <span data-refresh-status>Live · diperbarui otomatis tiap 30 detik</span>
            </p>
            {{-- Tombol di bawah baru tampil bila JavaScript aktif --}}
            <button type="button" class="cs-btn cs-btn-ghost cs-btn-sm" data-refresh-toggle aria-pressed="false" hidden>Jeda pembaruan</button>
            <button type="button" class="cs-btn cs-btn-secondary cs-btn-sm" data-print hidden>
                <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
                Cetak Laporan
            </button>
        </div>
    </header>

    {{-- ===================== FILTER PERIODE ===================== --}}
    <section aria-labelledby="filter-heading" class="cs-card cs-card-body cs-no-print mb-6">
        <h2 id="filter-heading" class="sr-only">Filter periode analisis</h2>
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="start_date" class="cs-form-label">Dari tanggal</label>
                    <input type="date" id="start_date" name="start" value="{{ request('start', $period['start']) }}"
                           class="cs-form-control w-auto">
                </div>
                <div>
                    <label for="end_date" class="cs-form-label">Sampai tanggal</label>
                    <input type="date" id="end_date" name="end" value="{{ request('end', $period['end']) }}"
                           class="cs-form-control w-auto">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="cs-btn cs-btn-primary">Terapkan Filter</button>
                    @if (request()->has('start') || request()->has('end'))
                        <a href="{{ route('dashboard') }}" class="cs-btn cs-btn-secondary">Reset</a>
                    @endif
                </div>
            </div>

            <dl class="space-y-1 text-xs sm:text-right">
                <div>
                    <dt class="font-medium text-ink-body">Periode terpilih</dt>
                    <dd class="text-ink-muted">
                        <time datetime="{{ $period['start'] }}">{{ \Carbon\Carbon::parse($period['start'])->translatedFormat('d M Y') }}</time>
                        s/d
                        <time datetime="{{ $period['end'] }}">{{ \Carbon\Carbon::parse($period['end'])->translatedFormat('d M Y') }}</time>
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-ink-body">Jam tersedia per ruang</dt>
                    {{-- Service mengirim angka mentah (mis. 15.0716…); dibulatkan hanya untuk tampilan --}}
                    <dd class="tabular-nums text-ink-muted">{{ round($period['available_hours'], 1) }} jam</dd>
                </div>
            </dl>
        </form>
    </section>

    {{-- ===================== KPI ===================== --}}
    <section aria-labelledby="kpi-heading" class="mb-8">
        <h2 id="kpi-heading" class="sr-only">Ringkasan indikator utama</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- KPI 1: Status live ruang --}}
            <article class="cs-card cs-card-body">
                <h3 class="cs-card-title">Status Ruang Saat Ini</h3>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="cs-stat-value">{{ $totalRooms }}</span>
                    <span class="cs-stat-unit">total ruang</span>
                </p>
                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Rincian status live ruang">
                    <li class="cs-status-indicator" data-status="available">
                        <span class="cs-status-dot" aria-hidden="true"></span>{{ $summary['available_now'] }} Tersedia
                    </li>
                    <li class="cs-status-indicator" data-status="booked">
                        <span class="cs-status-dot" aria-hidden="true"></span>{{ $summary['booked_now'] }} Dipesan
                    </li>
                    <li class="cs-status-indicator" data-status="occupied">
                        <span class="cs-status-dot" aria-hidden="true"></span>{{ $summary['occupied_now'] }} Terpakai
                    </li>
                    <li class="cs-status-indicator" data-status="maintenance">
                        <span class="cs-status-dot" aria-hidden="true"></span>{{ $maintenanceRooms->count() }} Perawatan
                    </li>
                </ul>
            </article>

            {{-- KPI 2: Terjadwal vs aktual --}}
            <article class="cs-card cs-card-body">
                <h3 class="cs-card-title">Utilisasi Rata-rata</h3>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="cs-stat-value">{{ $avgUtilRate }}<span class="text-xl text-ink-muted">%</span></span>
                    <span class="cs-stat-unit">aktual</span>
                </p>
                <div class="mt-3 space-y-2">
                    <div>
                        <p class="cs-caption flex justify-between"><span>Aktual</span><span class="tabular-nums">{{ $avgUtilRate }}%</span></p>
                        <x-progress-bar :value="$avgUtilRate" label="Rata-rata utilisasi aktual seluruh ruang" />
                    </div>
                    <div>
                        <p class="cs-caption flex justify-between"><span>Terjadwal</span><span class="tabular-nums">{{ $avgScheduledRate }}%</span></p>
                        <x-progress-bar :value="$avgScheduledRate" color="bg-secondary" label="Rata-rata utilisasi terjadwal seluruh ruang" />
                    </div>
                </div>
            </article>

            {{-- KPI 3: Booking & ghost booking --}}
            <article class="cs-card cs-card-body">
                <h3 class="cs-card-title">Booking Disetujui</h3>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="cs-stat-value">{{ $totalApproved }}</span>
                    <span class="cs-stat-unit">pada periode</span>
                </p>
                <p class="cs-caption mt-3">
                    <strong @class(['font-semibold', 'text-danger' => $totalGhosts > 0, 'text-ink-body' => $totalGhosts === 0])>
                        {{ $totalGhosts }} ghost booking
                    </strong>
                    — disetujui tetapi tidak check-in lebih dari {{ $ghostTolerance }} menit setelah mulai.
                </p>
            </article>

            {{-- KPI 4: Distribusi kategori --}}
            <article class="cs-card cs-card-body">
                <h3 class="cs-card-title">Distribusi Pemanfaatan</h3>
                <dl class="mt-3 space-y-2 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-1.5 text-ink-soft">
                            <span class="cs-status-dot" data-status="underutilized" aria-hidden="true"></span>Underutilized (&lt; 30%)
                        </dt>
                        <dd class="font-semibold tabular-nums text-warning">{{ $summary['underutilized'] }} ruang</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-1.5 text-ink-soft">
                            <span class="cs-status-dot" data-status="normal" aria-hidden="true"></span>Normal (30–79,9%)
                        </dt>
                        <dd class="font-semibold tabular-nums text-success">{{ $normalCount }} ruang</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-1.5 text-ink-soft">
                            <span class="cs-status-dot" data-status="overutilized" aria-hidden="true"></span>Overutilized (&ge; 80%)
                        </dt>
                        <dd class="font-semibold tabular-nums text-danger">{{ $summary['overutilized'] }} ruang</dd>
                    </div>
                </dl>
            </article>
        </div>
    </section>

    {{-- ===================== RINGKASAN LAPORAN (FE-06) ===================== --}}
    <section aria-labelledby="report-heading" class="mb-8">
        <h2 id="report-heading" class="cs-section-title">Ringkasan Laporan Utilisasi</h2>
        <p class="cs-section-description">Setiap angka berasal dari UtilizationDashboardService untuk periode terpilih; total adalah penjumlahan nilai per ruang.</p>

        <div class="cs-table-wrap mt-4" role="region" aria-labelledby="report-heading" tabindex="0">
            <table class="cs-table">
                <caption class="sr-only">Ringkasan metrik utilisasi beserta definisinya</caption>
                <thead>
                    <tr>
                        <th scope="col">Metrik</th>
                        <th scope="col" class="text-right">Nilai</th>
                        <th scope="col">Definisi &amp; sumber</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" class="cs-metric-name">Total ruang</th>
                        <td class="cs-cell-num cs-metric-value">{{ $totalRooms }}</td>
                        <td class="text-ink-soft">Seluruh ruang & laboratorium yang dihitung (<code>summary.total_rooms</code>).</td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Jam tersedia</th>
                        <td class="cs-cell-num cs-metric-value">{{ $totalAvailableHours }} jam</td>
                        <td class="text-ink-soft">
                            Σ jam operasional {{ sprintf('%02d.00', $openingHour) }}–{{ sprintf('%02d.00', $closingHour) }} hari kerja yang sudah berlalu
                            ({{ round($period['available_hours'], 1) }} jam per ruang × jumlah ruang).
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Jam terjadwal</th>
                        <td class="cs-cell-num cs-metric-value">{{ $totalScheduledHours }} jam</td>
                        <td class="text-ink-soft">Σ jam jadwal resmi + booking disetujui yang jatuh di jam operasional (<code>scheduled_hours</code>).</td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Jam aktual</th>
                        <td class="cs-cell-num cs-metric-value">{{ $totalActualHours }} jam</td>
                        <td class="text-ink-soft">Σ durasi check-in sampai check-out yang tercatat lewat QR (<code>actual_hours</code>).</td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Utilisasi terjadwal rata-rata</th>
                        <td class="cs-cell-num cs-metric-value">{{ $avgScheduledRate }}%</td>
                        <td class="text-ink-soft">Rata-rata <code>scheduled_rate</code> per ruang = jam terjadwal ÷ jam tersedia × 100.</td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Utilisasi aktual rata-rata</th>
                        <td class="cs-cell-num cs-metric-value">{{ $avgUtilRate }}%</td>
                        <td class="text-ink-soft">
                            Rata-rata <code>utilization_rate</code> per ruang. Ruang kelas: jam aktual ÷ jam tersedia × 100;
                            laboratorium: (jam aktual + waktu setup − penalti ghost booking) ÷ jam tersedia × 100.
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Ruang underutilized / normal / overutilized</th>
                        <td class="cs-cell-num cs-metric-value">{{ $summary['underutilized'] }} / {{ $normalCount }} / {{ $summary['overutilized'] }}</td>
                        <td class="text-ink-soft">Kategori dari Service: &lt; 30% underutilized, 30–79,9% normal, ≥ 80% overutilized.</td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Ghost booking</th>
                        <td class="cs-cell-num cs-metric-value">{{ $totalGhosts }} dari {{ $totalApproved }}</td>
                        <td class="text-ink-soft">
                            Booking disetujui yang tidak check-in lebih dari {{ $ghostTolerance }} menit setelah mulai (status no_show),
                            dibandingkan seluruh booking disetujui pada periode.
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="cs-metric-name">Konflik jadwal</th>
                        <td class="cs-cell-num text-ink-muted">Tidak tersedia</td>
                        <td class="text-ink-soft">
                            Pengajuan yang bentrok langsung ditolak saat submit dan tidak disimpan, sehingga jumlahnya belum tercatat di sistem.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ===================== UNDER / OVER UTILIZED ===================== --}}
    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="under-heading" class="cs-card cs-card-body">
            <h2 id="under-heading" class="cs-section-title">Ruang Underutilized</h2>
            <p class="cs-section-description">Utilisasi aktual di bawah 30% pada periode terpilih.</p>
            @if ($underRooms->isEmpty())
                <p class="cs-caption mt-4">Tidak ada ruang underutilized pada periode ini.</p>
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
            <h2 id="over-heading" class="cs-section-title">Ruang Overutilized</h2>
            <p class="cs-section-description">Utilisasi aktual 80% atau lebih pada periode terpilih.</p>
            @if ($overRooms->isEmpty())
                <p class="cs-caption mt-4">Tidak ada ruang overutilized pada periode ini.</p>
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
            Disusun dari kategori utilisasi, ghost booking, dan status ruang pada periode terpilih.
            Bersifat pendukung keputusan; keputusan akhir tetap pada pimpinan fakultas. Keputusan permanen
            (mis. alih fungsi ruang) sebaiknya didasarkan pada perbandingan beberapa periode, bukan satu periode.
        </p>

        @if (! $hasRooms)
            <div class="cs-card mt-4">
                <div class="cs-empty-state">
                    <p class="cs-empty-state-title">Belum ada data ruang untuk dianalisis.</p>
                    <p class="mt-1">Rekomendasi muncul setelah ruang, jadwal, dan booking tercatat.
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
                                {{ $roomRates($underRooms) }} di bawah 30%. Evaluasi pemindahan sebagian jadwal/booking dari ruang
                                padat ke ruang yang kurang dimanfaatkan dengan kapasitas dan tipe ruang yang sesuai.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori over/underutilized.</p>
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
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori overutilized.</p>
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
                            <p class="cs-caption mt-2">Dasar data: utilisasi aktual per ruang &amp; kategori underutilized.</p>
                        </article>
                    </li>
                @endif

                @if ($ghostRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Evaluasi prosedur booking &amp; check-in</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $totalGhosts }} dari {{ $totalApproved }} booking disetujui berakhir no-show:
                                {{ $ghostRooms->map(fn ($room) => $room['room_code'] . ' (' . $room['ghost_bookings'] . ')')->join(', ') }}.
                                Evaluasi konfirmasi ulang menjelang jadwal, pelepasan slot setelah toleransi {{ $ghostTolerance }} menit,
                                dan aturan bagi peminjam yang berulang kali tidak hadir.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: ghost booking &amp; booking disetujui per ruang.</p>
                        </article>
                    </li>
                @endif

                @if ($maintenanceRooms->isNotEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Pemulihan kapasitas ruang</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ $maintenanceRooms->pluck('room_code')->join(', ') }} sedang dalam perawatan dan tidak dapat dibooking.
                                Prioritaskan perbaikan agar kapasitas kembali tersedia.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: status live maintenance.</p>
                        </article>
                    </li>
                @endif

                @if ($overRooms->isEmpty() && $underRooms->isEmpty() && $ghostRooms->isEmpty() && $maintenanceRooms->isEmpty())
                    <li class="cs-card cs-card-body">
                        <article>
                            <h3 class="font-semibold text-ink">Pertahankan pola jadwal</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                Semua ruang berada pada kategori normal, tanpa ghost booking dan tanpa ruang dalam perawatan.
                            </p>
                            <p class="cs-caption mt-2">Dasar data: kategori utilisasi, ghost booking, status live.</p>
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
                <caption class="sr-only">Utilisasi terjadwal, aktual, ghost booking, dan kategori setiap ruang pada periode terpilih</caption>
                <thead>
                    <tr>
                        <th scope="col">Ruangan &amp; Tipe</th>
                        <th scope="col">Status Live</th>
                        <th scope="col">Terjadwal</th>
                        <th scope="col">Aktual &amp; Utilisasi</th>
                        <th scope="col" class="text-right">Ghost Booking</th>
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
                                <span class="block text-[11px] text-ink-muted">{{ $room['ghost_rate'] }}% no-show</span>
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
                                    <p class="mt-1">Data utilisasi dihitung dari ruang, jadwal, dan booking yang tercatat.
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
                <li>Terjadwal = jadwal resmi + booking disetujui. Aktual = durasi check-in sampai check-out; rumus laboratorium ikut memperhitungkan waktu setup dan penalti ghost booking.</li>
                <li>Ghost booking = booking disetujui yang tidak check-in lebih dari {{ $ghostTolerance }} menit setelah jadwal mulai.</li>
                <li>Konflik jadwal dicegah saat pengajuan (pengajuan yang bentrok ditolak), sehingga tidak muncul sebagai metrik di dashboard.</li>
            </ul>
        </div>
    </aside>
@endsection
