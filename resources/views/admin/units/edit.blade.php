{{-- C5 · Ubah Unit Akademik (PUT admin.units.update). Variabel: $unit. --}}
@extends('layouts.app')

@section('title', 'Ubah Unit ' . $unit->code)
@section('inline_error_fields', 'code,name,description')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('admin.units.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke unit akademik</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah Unit {{ $unit->code }}</h1>
        </header>

        <x-route-form route="admin.units.update" :params="$unit" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('admin.units.partials.form', ['unit' => $unit])
            <div class="cs-form-actions">
                <a href="{{ route('admin.units.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </x-route-form>
    </section>
@endsection
