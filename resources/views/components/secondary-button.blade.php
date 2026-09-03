<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border rounded-md font-semibold text-xs text-dark-muted uppercase tracking-widest hover:text-dark-text focus:outline-none focus:ring-2 focus:ring-corp focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
