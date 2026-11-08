{{-- Notifikasi pesan dari Controller (ditampilkan layout tepat di bawah header halaman):
     - redirect()->with('success', '...')  -> kartu hijau
     - redirect()->with('error', '...')    -> kartu merah
     - validasi gagal / withErrors([...])  -> daftar galat merah
     Tombol × (data-dismiss) menutup kartu; tanpa JS tombol disembunyikan (atribut hidden dibuka oleh campus-space.js). --}}

@if (session('success'))
    <div role="status" class="cs-alert cs-alert-success" data-dismissible>
        <x-icon name="check-circle" class="cs-alert-icon h-5 w-5" />
        <p>{{ session('success') }}</p>
        <button type="button" class="cs-alert-dismiss" data-dismiss hidden aria-label="Tutup pesan">&times;</button>
    </div>
@endif

@if (session('error'))
    <div role="alert" class="cs-alert cs-alert-danger" data-dismissible>
        <x-icon name="alert" class="cs-alert-icon h-5 w-5" />
        <p>{{ session('error') }}</p>
        <button type="button" class="cs-alert-dismiss" data-dismiss hidden aria-label="Tutup pesan">&times;</button>
    </div>
@endif

@if ($errors->any())
    @php
        // Halaman yang menampilkan @error di bawah field mendeklarasikan nama field-nya lewat
        // @section('inline_error_fields', 'field_a,field_b'). Pesan field tersebut tidak diulang
        // di sini (cukup ringkasan), sehingga satu galat tidak tampil dua kali.
        $inlineFields = array_filter(array_map('trim', explode(',', $__env->yieldContent('inline_error_fields'))));
        $inlineCount = count(array_intersect($errors->keys(), $inlineFields));
        $otherMessages = collect($errors->getMessages())->except($inlineFields)->flatten();
    @endphp
    <div role="alert" class="cs-alert cs-alert-danger" data-dismissible>
        <x-icon name="alert" class="cs-alert-icon h-5 w-5" />
        <div>
            <p class="cs-alert-title">Permintaan tidak dapat diproses.</p>
            @if ($inlineCount > 0)
                <p>{{ $inlineCount }} isian perlu diperbaiki. Pesannya ditampilkan di bawah masing-masing isian.</p>
            @endif
            @if ($otherMessages->isNotEmpty())
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($otherMessages as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <button type="button" class="cs-alert-dismiss" data-dismiss hidden aria-label="Tutup pesan">&times;</button>
    </div>
@endif
