{{-- Paginasi Campus Space (menimpa view bawaan Laravel `pagination::tailwind`, dipakai semua `$x->links()`).
     Info "Menampilkan 1–10 dari 32", tombol Sebelumnya/Berikutnya, dan nomor halaman.
     Query string filter tetap ikut bila controller memanggil ->withQueryString(). --}}
@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="cs-pagination" aria-label="Navigasi halaman">
        <p class="cs-pagination-info">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <ul class="cs-pagination-list">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="cs-page-link" aria-disabled="true">Sebelumnya</span>
                    @else
                        <a class="cs-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="cs-page-link" aria-disabled="true">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span class="cs-page-link" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="cs-page-link" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a class="cs-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
                    @else
                        <span class="cs-page-link" aria-disabled="true">Berikutnya</span>
                    @endif
                </li>
            </ul>
        @endif
    </nav>
@endif
