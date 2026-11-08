{{-- Dialog konfirmasi berbasis <dialog> HTML (bukan confirm() bawaan browser). Dua mode:

     1) Umum (tanpa action) — dipasang sekali di layout dengan id "cs-confirm-dialog".
        Semua <form data-confirm="pesan"> (termasuk x-route-form confirm="...") memakai dialog ini
        lewat campus-space.js. Tanpa JS, form langsung terkirim.

     2) Dengan form sendiri (action diisi) — mis. Tolak booking yang wajib alasan:
          <button type="button" class="cs-btn cs-btn-danger" data-dialog-open="reject-12" hidden>Tolak</button>
          <x-confirm-dialog id="reject-12" title="Tolak peminjaman?" :action="route('bookings.reject', $booking)"
                            confirm="Tolak Peminjaman" reason="rejection_reason" reason-label="Alasan penolakan">
              Peminjam akan melihat alasan ini.
          </x-confirm-dialog>
        Tombol pembuka (data-dialog-open) disembunyikan sampai JS aktif; sediakan fallback <noscript> bila perlu.

     <dialog> modal sudah mengunci fokus dan bisa ditutup dengan Esc. --}}
@props([
    'id',
    'title' => 'Konfirmasi',
    'action' => null,
    'method' => 'POST',
    'confirm' => 'Ya, lanjutkan',
    'tone' => 'danger',
    'reason' => null,
    'reasonLabel' => 'Alasan',
    'reasonMax' => 1000,
])

<dialog id="{{ $id }}" class="cs-dialog" aria-labelledby="{{ $id }}-title" {{ $attributes }}>
    @if ($action)
        <form method="POST" action="{{ $action }}" class="cs-dialog-body" data-cs-validate>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <h2 id="{{ $id }}-title" class="cs-dialog-title">{{ $title }}</h2>
            @if ($slot->isNotEmpty())
                <p class="text-ink-soft">{{ $slot }}</p>
            @endif
            @if ($reason)
                <div>
                    <label for="{{ $id }}-reason" class="cs-form-label">
                        {{ $reasonLabel }} <span class="cs-form-required" aria-hidden="true">*</span>
                    </label>
                    <textarea id="{{ $id }}-reason" name="{{ $reason }}" rows="3" required maxlength="{{ $reasonMax }}"
                              data-not-blank class="cs-form-control"></textarea>
                    <p class="cs-form-hint">Wajib diisi, maksimal {{ $reasonMax }} karakter.</p>
                </div>
            @endif
            <div class="cs-dialog-actions">
                <button type="button" class="cs-btn cs-btn-ghost" data-dialog-close>Batal</button>
                <button type="submit" class="cs-btn cs-btn-{{ $tone === 'danger' ? 'danger' : 'primary' }}">{{ $confirm }}</button>
            </div>
        </form>
    @else
        <form method="dialog" class="cs-dialog-body">
            <h2 id="{{ $id }}-title" class="cs-dialog-title">{{ $title }}</h2>
            <p class="text-ink-soft" data-confirm-message></p>
            <div class="cs-dialog-actions">
                <button type="submit" value="cancel" class="cs-btn cs-btn-ghost">Batal</button>
                <button type="submit" value="confirm" class="cs-btn cs-btn-primary" data-confirm-accept>{{ $confirm }}</button>
            </div>
        </form>
    @endif
</dialog>
