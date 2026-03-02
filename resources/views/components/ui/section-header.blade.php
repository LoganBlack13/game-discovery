@props([
    'title',
    'subtitle' => null,
    'id' => null,
])

<header {{ $attributes->merge(['id' => $id])->class('mb-6 flex flex-col gap-2 sm:mb-8 sm:flex-row sm:items-end sm:justify-between') }}>
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary/80">
            {{ $subtitle ?? 'Signal' }}
        </p>
        <h2 class="font-display text-xl font-semibold text-base-content sm:text-2xl">
            {{ $title }}
        </h2>
    </div>
    @if (trim($slot) !== '')
        <div class="mt-2 flex items-center gap-3 sm:mt-0">
            {{ $slot }}
        </div>
    @endif
</header>

