{{-- Pindai QR (scan.index) — referensi 06-mhs-pindai-qr.
     - Kamera / foto: html5-qrcode (resources/js/qr.js, dimuat bila ada [data-qr-scanner]). Tombol berbahasa Indonesia
       dibuat sendiri: "Izinkan Kamera" (data-qr-start) dan "Pindai dari Foto" (data-qr-file).
       QR berisi URL /scan/{token}; hasil pindai membuka halaman konfirmasi scan.room di origin yang sedang dipakai.
       Kamera browser hanya berjalan di HTTPS atau localhost; lewat IP LAN (http) gunakan kamera bawaan ponsel atau foto.
     - Manual: POST scan.manual, field `code` = URL QR, token QR ruangan, atau kode cadangan booking.
     - Panel kanan [data-qr-scanner-status] menampilkan status: siap memindai -> QR terbaca -> galat. --}}
@extends('layouts.app')

@section('title', 'Pindai QR')
@section('inline_error_fields', 'code')

@section('page_header')
    <x-page-header eyebrow="Penggunaan aktual" title="Check-in / Check-out." crumb="Pindai QR"
                   subtitle="Pindai QR yang ditempel di ruangan untuk mencatat penggunaan Anda." />
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="cs-card cs-card-body space-y-5 lg:col-span-3" aria-labelledby="scanner-heading">
            <h2 id="scanner-heading" class="text-xl font-extrabold text-ink">Pindai QR ruangan</h2>

            <div class="rounded-xl border border-dashed border-[color:var(--cs-color-border-strong)] p-4 text-center">
                <div id="qr-reader" data-qr-scanner class="mx-auto w-full max-w-sm"></div>
                <x-icon name="camera" class="mx-auto h-10 w-10 text-ink-muted" data-qr-placeholder />
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button type="button" class="cs-btn cs-btn-primary" data-qr-start hidden>
                        <x-icon name="camera" class="h-4 w-4" /> Izinkan Kamera
                    </button>
                    <label class="cs-btn cs-btn-secondary" data-qr-file-label hidden>
                        <x-icon name="image" class="h-4 w-4" /> Pindai dari Foto
                        <input type="file" accept="image/*" class="sr-only" data-qr-file>
                    </label>
                </div>
                <noscript><p class="mt-3 text-sm text-ink-muted">JavaScript nonaktif: gunakan tautan QR manual di bawah.</p></noscript>
            </div>

            <p class="text-sm text-ink-soft">
                Kamera memerlukan HTTPS atau localhost. Jika akses dari ponsel menggunakan HTTP, pindai QR lewat
                kamera bawaan ponsel (QR berisi tautan) atau pilih foto QR dari perangkat.
            </p>

            <details class="border-t border-border pt-4" @if ($errors->has('code') || old('code')) open @endif>
                <summary class="cursor-pointer font-semibold text-ink">Masukkan tautan QR secara manual</summary>
                <form method="POST" action="{{ route('scan.manual') }}" class="mt-4 space-y-3" data-cs-validate>
                    @csrf
                    <div>
                        <label for="code" class="cs-form-label">Tautan QR atau kode cadangan peminjaman</label>
                        <input id="code" name="code" type="text" value="{{ old('code') }}" required maxlength="255"
                               autocomplete="off" autocapitalize="off" spellcheck="false" data-trim
                               placeholder="http://…/scan/… atau kode dari detail peminjaman"
                               class="cs-form-control font-mono text-sm"
                               aria-describedby="code-hint @error('code') code-error @enderror"
                               @error('code') aria-invalid="true" @enderror>
                        <p id="code-hint" class="cs-form-hint">Kode cadangan ada di halaman detail peminjaman yang sudah disetujui.</p>
                        <x-field-error for="code" />
                    </div>
                    <button type="submit" class="cs-btn cs-btn-primary">Lanjutkan</button>
                </form>
            </details>
        </section>

        <aside class="cs-card cs-card-body flex flex-col items-center justify-center gap-3 text-center lg:col-span-2" aria-live="polite">
            <span class="cs-icon-chip h-14 w-14"><x-icon name="qr" /></span>
            <p class="text-lg font-extrabold text-ink" data-qr-scanner-title>Siap memindai</p>
            <p class="max-w-xs text-sm text-ink-soft" data-qr-scanner-status>
                Setelah QR terbaca, peminjaman Anda di ruangan itu akan ditampilkan untuk dikonfirmasi.
            </p>
        </aside>
    </div>
@endsection
