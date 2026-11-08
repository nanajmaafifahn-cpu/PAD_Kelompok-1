{{-- C5 · Ubah Unit Akademik (PUT admin.units.update). Variabel: $unit. --}}
@extends('layouts.app')

@section('title', 'Ubah Unit ' . $unit->code)
@section('inline_error_fields', 'code,name,description')

@section('page_header')
    <x-page-header eyebrow="Administrasi" title="Ubah unit {{ $unit->code }}." crumb="Unit Akademik" subtitle="{{ $unit->name }}">
        <x-slot:actions>
            <a href="{{ route('admin.units.index') }}" class="cs-btn cs-btn-secondary">Kembali ke unit akademik</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form ubah unit akademik">

        <x-route-form route="admin.units.update" :params="$unit" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('admin.units.partials.form', ['unit' => $unit])
            <div class="cs-form-actions">
                <a href="{{ route('admin.units.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </x-route-form>
    </section>
@endsection
