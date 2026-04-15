@props([
    'type' => 'info',
    'message' => '',
])

@php
    $styles = [
        'info' => [
            'background' => 'var(--blue-bg)',
            'color' => 'var(--blue)',
            'border' => 'var(--blue-bd)',
        ],
        'warn' => [
            'background' => 'var(--amber-bg)',
            'color' => 'var(--amber)',
            'border' => 'var(--amber-bd)',
        ],
        'danger' => [
            'background' => 'var(--red-bg)',
            'color' => 'var(--red)',
            'border' => 'var(--red-bd)',
        ],
    ];

    $style = $styles[$type] ?? $styles['info'];
@endphp

<div class="rounded-[8px] p-[10px_14px] mb-[14px]"
     style="background: {{ $style['background'] }}; border: 0.5px solid {{ $style['border'] }};">
    <p class="text-[12px] leading-[1.6] m-0 font-normal"
       style="color: {{ $style['color'] }};">
        {{ $message ?: $slot }}
    </p>
</div>
