@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text placeholder-dark-muted focus:border-corp focus:ring-1 focus:ring-corp outline-none transition-colors']) }}>
