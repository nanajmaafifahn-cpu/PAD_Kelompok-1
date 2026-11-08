{{-- Cetak QR Ruangan (rooms.qr.index, pengelola/admin) — referensi 11-pengelola-cetak-qr.
     Variabel dari RoomController@qrIndex:
       $rooms    collection Room (diurutkan per kode)
       $baseUrl  config('app.url') tanpa garis miring akhir
     Isi QR = alamat dasar + route('scan.room', qr_token, false). Gambar QR dibuat resources/js/qr.js (npm qrcode)
     pada setiap <canvas data-qr-path>; mengubah alamat dasar langsung menggambar ulang semua QR.
     "Perbarui QR" per kartu = POST rooms.qr.regenerate (token baru, QR lama tidak berlaku; dialog konfirmasi).
     Cetak: 2 kartu per halaman A4, QR 7 cm, tanpa sidebar/tombol (@media print di campus-space.css). --}}
@extends('layouts.app')

@section('title', 'Cetak QR Ruangan')

@section('page_header')
    {{-- Header tidak ikut dicetak agar tiap halaman A4 berisi tepat 2 kartu QR --}}
    <x-page-header class="cs-no-print" eyebrow="Check-in di lokasi" title="QR ruangan." crumb="Cetak QR Ruangan"
                   subtitle="Cetak dan tempel QR pada pintu ruangan yang sesuai.">
        <x-slot:actions>
            <button type="button" class="cs-btn cs-btn-primary" data-print hidden>
                <x-icon name="printer" class="h-4 w-4" /> Cetak QR
            </button>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-card cs-card-body cs-no-print mb-6 space-y-3" aria-labelledby="base-url-label">
        <label id="base-url-label" for="qr-base-url" class="cs-form-label">Alamat Campus Space yang bisa dibuka ponsel</label>
        <input id="qr-base-url" type="url" value="{{ $baseUrl }}" data-qr-base class="cs-form-control font-mono"
               aria-describedby="qr-base-url-hint">
        <p id="qr-base-url-hint" class="text-sm text-ink-soft">
            Default dari <code class="cs-code">APP_URL</code>. Untuk dipindai lewat Wi-Fi, ganti <code class="cs-code">localhost</code>
            dengan IP komputer server (misalnya <code class="cs-code">http://192.168.1.10:8000</code>) lalu jalankan
            <code class="cs-code">php artisan serve --host=0.0.0.0 --port=8000</code>. QR digambar ulang otomatis saat alamat diubah.
        </p>
        @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            <p class="cs-alert cs-alert-warning mb-0">Gambar QR membutuhkan aset Vite. Jalankan <code>npm install</code> lalu <code>npm run build</code>.</p>
        @endunless
    </section>

    @if ($rooms->isEmpty())
        <div class="cs-card">
            <x-empty-state title="Belum ada ruang" text="QR dibuat otomatis untuk setiap ruang yang ditambahkan.">
                <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">Tambah Ruang</a>
            </x-empty-state>
        </div>
    @else
        <ul class="cs-qr-grid" aria-label="Kartu QR per ruang">
            @foreach ($rooms as $room)
                @php $path = route('scan.room', $room, false); @endphp
                <li class="cs-qr-card">
                    <p class="cs-eyebrow mb-0">Campus Space · FRI</p>
                    <p class="cs-qr-card-name">{{ $room->name }}</p>
                    <p class="text-lg text-ink-body">{{ $room->code }}</p>
                    <canvas class="cs-qr-canvas" data-qr-path="{{ $path }}" width="320" height="320"
                            role="img" aria-label="QR check-in ruang {{ $room->name }}"></canvas>
                    <p class="font-semibold text-ink">Pindai untuk check-in / check-out</p>
                    <p class="break-all font-mono text-[10px] text-ink-muted" data-qr-url-for="{{ $path }}">{{ $baseUrl . $path }}</p>
                    <x-route-form route="rooms.qr.regenerate" :params="$room" class="cs-no-print"
                                  confirm="Perbarui QR {{ $room->name }}? QR lama yang sudah tertempel tidak berlaku lagi dan harus diganti.">
                        <button type="submit" class="cs-btn cs-btn-ghost cs-btn-sm">Perbarui QR<span class="sr-only"> {{ $room->name }}</span></button>
                    </x-route-form>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
