@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-dark-muted']) }}>
    {{ $value ?? $slot }}
</label>
