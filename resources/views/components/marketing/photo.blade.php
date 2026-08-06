@props([
    'src' => null,
    'alt' => '',
    'ratio' => 'aspect-[4/3]',
    'rounded' => 'rounded-3xl',
    'label' => 'Photo',
    'eager' => false,
])

@php
    $path = $src ? ltrim($src, '/') : null;
    $hasImage = $path !== null && is_file(public_path($path));
@endphp

@if ($hasImage)
    <img
        src="{{ asset($path) }}"
        alt="{{ $alt }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $eager ? 'high' : 'auto' }}"
        decoding="async"
        {{ $attributes->class(['h-full w-full object-cover', $ratio, $rounded]) }}
    >
@else
    <div
        role="img"
        aria-label="{{ $alt ?: $label }}"
        {{ $attributes->class([
            'relative flex flex-col items-center justify-center gap-2 overflow-hidden border border-dashed border-current/25 bg-gradient-to-br from-signal-soft via-mist to-muted p-4 text-center text-muted-foreground',
            $ratio,
            $rounded,
        ]) }}
    >
        <svg viewBox="0 0 24 24" fill="none" class="size-8 opacity-60" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="8.5" cy="10" r="1.75" stroke="currentColor" stroke-width="1.5"/>
            <path d="m4 17 4.5-4.5a2 2 0 0 1 2.8 0L16 17m-1.5-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14.8"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p class="text-sm font-semibold text-ink">{{ $label }}</p>
        @if ($path)
            <p class="max-w-[22ch] text-xs break-words opacity-80">
                Drop an image at <code class="font-mono">public/{{ $path }}</code>
            </p>
        @endif
    </div>
@endif
