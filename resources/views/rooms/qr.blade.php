{{-- Cetak QR Ruangan (rooms.qr.index, khusus pengelola/admin).
     Variabel dari RoomController@qrIndex:
       $rooms    collection Room (diurutkan per kode)
       $baseUrl  config('app.url') tanpa garis miring akhir
     Isi QR = alamat dasar + route('scan.room', qr_token, false). Gambar QR dibuat di browser oleh
     resources/js/qr.js (npm qrcode) pada setiap <canvas data-qr-path>; mengubah alamat dasar langsung
     menggambar ulang semua QR. Tombol "Perbarui QR" = POST rooms.qr.regenerate (QR lama tidak berlaku). --}}
@extends('layouts.app')

@section('title', 'Cetak QR Ruangan')

@section('content')
    <header class="cs-page-header cs-no-print">
        <div>
            <a href="{{ route('rooms.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar ruang</a>
            <h1 class="cs-page-title mt-2">Cetak QR Ruangan</h1>
            <p class="cs-page-description">Cetak lalu tempel satu QR di pintu setiap ruang. Peminjam memindainya untuk check-in dan check-out.</p>
        </div>
        <button type="button" class="cs-btn cs-btn-primary" data-print hidden>Cetak</button>
    </header>

    <section class="cs-card cs-card-body cs-no-print mb-6 space-y-2" aria-labelledby="base-url-label">
        <label id="base-url-label" for="qr-base-url" class="cs-form-label">Alamat dasar aplikasi</label>
        <input id="qr-base-url" type="url" value="{{ $baseUrl }}" data-qr-base class="cs-form-control font-mono text-sm md:w-96"
               aria-describedby="qr-base-url-hint">
        <p id="qr-base-url-hint" class="cs-form-hint">
            Default dari <code>APP_URL</code>. Agar QR bisa dipindai ponsel di jaringan yang sama, ganti <code>localhost</code>
            dengan IP LAN komputer ini (misalnya <code>http://192.168.1.10:8000</code>) dan jalankan
            <code>php artisan serve --host=0.0.0.0 --port=8000</code>.
        </p>
        @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            <p class="cs-alert cs-alert-warning mb-0">Gambar QR membutuhkan aset Vite. Jalankan <code>npm install</code> lalu <code>npm run build</code>.</p>
        @endunless
    </section>

    @if ($rooms->isEmpty())
        <div class="cs-empty-state">
            <p class="cs-empty-state-title">Belum ada ruang.</p>
            <p class="mt-1"><a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang</a> terlebih dahulu.</p>
        </div>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Kartu QR per ruang">
            @foreach ($rooms as $room)
                @php $path = route('scan.room', $room, false); @endphp
                <li class="cs-card cs-card-body flex flex-col items-center gap-2 text-center" style="break-inside: avoid">
                    <p class="cs-card-title">{{ $room->name }}</p>
                    <p class="cs-code">{{ $room->code }}</p>
                    <canvas data-qr-path="{{ $path }}" width="180" height="180"
                            role="img" aria-label="QR scan ruang {{ $room->name }}"></canvas>
                    <p class="break-all font-mono text-[10px] text-ink-muted" data-qr-url-for="{{ $path }}">{{ $baseUrl . $path }}</p>
                    <p class="cs-caption">Scan untuk check-in / check-out</p>
                    <form method="POST" action="{{ route('rooms.qr.regenerate', $room) }}" class="cs-no-print"
                          data-confirm="Perbarui QR {{ $room->name }}? QR lama yang sudah tertempel tidak berlaku lagi.">
                        @csrf
                        <button type="submit" class="cs-btn cs-btn-ghost cs-btn-sm">Perbarui QR</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
