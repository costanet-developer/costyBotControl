@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-corp text-start text-base font-medium text-corp bg-dark-card focus:outline-none focus:text-corp focus:bg-dark-card focus:border-corp transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-dark-muted hover:text-dark-text hover:bg-dark-card hover:border-dark-border focus:outline-none focus:text-dark-text focus:bg-dark-card focus:border-dark-border transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
