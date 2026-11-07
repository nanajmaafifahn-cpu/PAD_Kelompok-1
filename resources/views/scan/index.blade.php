{{-- Halaman pemindai QR ruangan (scan.index).
     - Kamera: html5-qrcode (resources/js/qr.js, dimuat otomatis bila ada [data-qr-scanner]).
       Hasil pindai berupa URL /scan/{token}; browser langsung membuka URL itu di origin yang sedang dipakai.
       Kamera browser hanya jalan di HTTPS atau localhost. Lewat IP LAN (http) gunakan aplikasi kamera ponsel:
       QR berisi URL, jadi cukup dibuka dari kamera bawaan.
     - Input manual: POST scan.manual, field `code` = URL QR, token QR ruangan, atau kode cadangan booking. --}}
@extends('layouts.app')

@section('title', 'Scan QR Ruangan')
@section('inline_error_fields', 'code')

@section('content')
    <section class="cs-container-narrow space-y-6" aria-labelledby="page-title">
        <header>
            <h1 id="page-title" class="cs-page-title">Scan QR Ruangan</h1>
            <p class="cs-page-description">
                Arahkan kamera ke QR di pintu ruangan. Sistem mencari peminjaman Anda di ruangan itu,
                lalu menawarkan check-in (bila sudah waktunya) atau check-out (bila Anda sedang memakai ruangan).
            </p>
        </header>

        <div class="cs-card cs-card-body space-y-3">
            <h2 class="cs-card-title">Kamera</h2>
            <div id="qr-reader" data-qr-scanner class="w-full max-w-sm"></div>
            <p data-qr-scanner-status class="cs-form-hint" aria-live="polite">
                Kamera aktif setelah izin diberikan. Bila tidak muncul, gunakan aplikasi kamera ponsel atau input manual di bawah.
            </p>
            <noscript><p class="cs-form-hint">JavaScript nonaktif: gunakan input manual.</p></noscript>
        </div>

        <form method="POST" action="{{ route('scan.manual') }}" class="cs-card cs-card-body space-y-3" data-cs-validate>
            @csrf
            <h2 class="cs-card-title">Input manual</h2>
            <div>
                <label for="code" class="cs-form-label">URL QR atau kode cadangan booking</label>
                <input id="code" name="code" type="text" value="{{ old('code') }}" required maxlength="255"
                       autocomplete="off" autocapitalize="off" spellcheck="false" data-trim
                       placeholder="http://…/scan/… atau kode dari detail booking"
                       class="cs-form-control font-mono text-sm"
                       aria-describedby="code-hint @error('code') code-error @enderror"
                       @error('code') aria-invalid="true" @enderror>
                <p id="code-hint" class="cs-form-hint">Kode cadangan ada di halaman detail peminjaman Anda yang sudah disetujui.</p>
                <x-field-error for="code" />
            </div>
            <button type="submit" class="cs-btn cs-btn-primary">Lanjutkan</button>
        </form>
    </section>
@endsection
