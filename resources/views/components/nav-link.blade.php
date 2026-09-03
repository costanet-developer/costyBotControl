@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-corp text-sm font-medium leading-5 text-dark-text focus:outline-none focus:border-corp transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-dark-muted hover:text-dark-text hover:border-dark-border focus:outline-none focus:text-dark-text focus:border-dark-border transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
