@props([
    'icon' => null,
    'label',
    'value' => null,
    'tone' => 'neutral',
])

<?php
    $toneStyles = [
        'info' => 'border-info/40 bg-info/5 text-base-content',
        'success' => 'border-success/40 bg-success/5 text-base-content',
        'warning' => 'border-warning/40 bg-warning/5 text-base-content',
        'error' => 'border-error/40 bg-error/5 text-base-content',
        'neutral' => 'border-base-content/15 bg-base-200/60 text-base-content',
    ];

    $toneClass = $toneStyles[$tone] ?? $toneStyles['neutral'];
?>

<div {{ $attributes->class("card card-compact rounded-box border {$toneClass}") }}>
    <div class="card-body gap-2 px-3 py-2.5">
        <div class="flex items-center gap-2">
            @if ($icon)
                <div class="flex size-6 items-center justify-center rounded-full bg-base-100/60 text-xs">
                    {!! $icon !!}
                </div>
            @endif
            <p class="truncate text-xs font-medium uppercase tracking-[0.18em]">
                {{ $label }}
            </p>
        </div>
        @if ($value)
            <p class="text-sm font-semibold leading-tight">
                {{ $value }}
            </p>
        @endif
        @if (trim($slot) !== '')
            <div class="mt-1 text-xs text-base-content/70">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

