{{-- Notifikasi pesan dari Controller:
     - redirect()->with('success', '...')  -> kotak hijau
     - redirect()->with('error', '...')    -> kotak merah
     - validasi gagal / withErrors([...])  -> daftar galat merah --}}

@if (session('success'))
    <div role="status" class="cs-alert cs-alert-success">
        <span aria-hidden="true" class="cs-alert-icon">✓</span>
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div role="alert" class="cs-alert cs-alert-danger">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
        <p>{{ session('error') }}</p>
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
    <div role="alert" class="cs-alert cs-alert-danger">
        <span aria-hidden="true" class="cs-alert-icon">!</span>
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
    </div>
@endif
