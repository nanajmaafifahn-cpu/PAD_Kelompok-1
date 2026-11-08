{{-- Pesan galat validasi server untuk satu field (pola FE-05).
     id = "{for}-error" agar bisa dirujuk aria-describedby pada input-nya.
     Contoh: <x-field-error for="participant_count" />
     Halaman yang memakai komponen ini juga perlu mendaftarkan nama field di
     @section('inline_error_fields', '...') agar pesan tidak diulang di alert atas. --}}
@props(['for'])

@error($for)
    <p id="{{ $for }}-error" class="cs-form-error">{{ $message }}</p>
@enderror
