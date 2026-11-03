{{-- Bar persentase. Nilai sudah dihitung & dibulatkan di Controller/Service;
     lebar bar dibatasi 100% oleh CSS (.cs-progress-bar max-width), bukan oleh rumus di view.
     Props:
       value  angka persen
       color  utility warna bar (default bg-primary dari token campus-space.css)
       label  teks untuk pembaca layar, mis. "Utilisasi aktual LAB-KOM-01" --}}
@props(['value', 'color' => 'bg-primary', 'label' => null])

<div class="cs-progress" role="progressbar"
     aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
     @if ($label) aria-label="{{ $label }}" @endif>
    <div class="cs-progress-bar {{ $color }}" style="width: {{ $value }}%"></div>
</div>
