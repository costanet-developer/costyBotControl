@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Paginación') }}" class="flex items-center justify-between gap-3 w-full">
        <p class="text-xs text-dark-muted">
            {{ __('Mostrando') }}
            <span class="font-semibold text-dark-text">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-semibold text-dark-text">{{ $paginator->lastItem() }}</span>
            {{ __('de') }}
            <span class="font-semibold text-dark-text">{{ $paginator->total() }}</span>
        </p>

        <ul class="flex items-center gap-1">
            {{-- Botón anterior --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="px-2.5 py-1.5 text-xs rounded-lg border border-dark-border bg-dark-card text-dark-muted/40 cursor-not-allowed select-none">
                        &larr;
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="px-2.5 py-1.5 text-xs rounded-lg border border-dark-border bg-dark-card text-dark-text hover:border-corp/50 hover:text-corp transition-colors" aria-label="Anterior">
                        &larr;
                    </a>
                </li>
            @endif

            {{-- Elementos de página --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="px-2 py-1.5 text-xs text-dark-muted select-none">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page">
                                <span class="px-2.5 py-1.5 text-xs rounded-lg bg-corp text-dark-bg font-semibold select-none">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}"
                                   class="px-2.5 py-1.5 text-xs rounded-lg border border-dark-border bg-dark-card text-dark-text hover:border-corp/50 hover:text-corp transition-colors">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón siguiente --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="px-2.5 py-1.5 text-xs rounded-lg border border-dark-border bg-dark-card text-dark-text hover:border-corp/50 hover:text-corp transition-colors" aria-label="Siguiente">
                        &rarr;
                    </a>
                </li>
            @else
                <li>
                    <span class="px-2.5 py-1.5 text-xs rounded-lg border border-dark-border bg-dark-card text-dark-muted/40 cursor-not-allowed select-none">
                        &rarr;
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
