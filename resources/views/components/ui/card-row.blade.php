@props([
    'title',
    'subtitle' => null,
    'id' => null,
    'cardWidth' => 'w-[280px]',
    'cardHeight' => 'h-[340px]',
    'showArrows' => true,
    'variant' => 'default',
])

<section
    {{ $attributes->merge(['id' => $id])->class('space-y-3 sm:space-y-4') }}
    aria-label="{{ $title }}"
    x-data="{
            isPointerDown: false,
            startX: 0,
            startScrollLeft: 0,
            scrollBy(direction) {
                const scroller = this.$refs.scroll;
                if (!scroller) {
                    return;
                }
                const first = scroller.firstElementChild;
                const cardWidth = first ? first.getBoundingClientRect().width : scroller.getBoundingClientRect().width * 0.8;
                const gap = 16; // gap-4
                let amount = cardWidth + gap;
                if (direction === 'left') {
                    amount *= -1;
                }
                scroller.scrollBy({ left: amount, behavior: 'smooth' });
            },
            onPointerDown(event) {
                const isPrimary = event.button === 0 || event.pointerType === 'touch' || event.pointerType === 'pen';
                if (!isPrimary) {
                    return;
                }
                this.isPointerDown = true;
                this.startX = event.clientX;
                this.startScrollLeft = this.$refs.scroll.scrollLeft;
            },
            onPointerMove(event) {
                if (!this.isPointerDown) {
                    return;
                }
                const dx = event.clientX - this.startX;
                this.$refs.scroll.scrollLeft = this.startScrollLeft - dx;
            },
            onPointerUp() {
                this.isPointerDown = false;
            },
        }"
>
    <x-ui.section-header
        :id="$id ? $id.'-heading' : null"
        :title="$title"
        :subtitle="$subtitle"
    >
        @isset($actions)
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        @endisset

        @if ($showArrows)
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-ghost btn-xs rounded-full border border-base-content/10 bg-base-100/40 text-base-content/80 hover:bg-base-100/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
                    aria-label="Scroll {{ strtolower($title) }} left"
                    x-on:click.prevent="scrollBy('left')"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6.75 10.5 12l5.25 5.25M9 6.75 3.75 12 9 17.25" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs rounded-full border border-base-content/10 bg-base-100/40 text-base-content/80 hover:bg-base-100/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base-100"
                    aria-label="Scroll {{ strtolower($title) }} right"
                    x-on:click.prevent="scrollBy('right')"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 6.75 13.5 12 8.25 17.25M15 6.75 20.25 12 15 17.25" />
                    </svg>
                </button>
            </div>
        @endif
    </x-ui.section-header>

    <div class="relative">
        <div
            x-ref="scroll"
            class="card-row-scroll -mx-4 flex gap-4 overflow-x-auto px-4 pb-4 scrollbar-hidden snap-x snap-mandatory sm:mx-0 sm:px-0"
            @pointerdown="onPointerDown($event)"
            @pointermove="onPointerMove($event)"
            @pointerup="onPointerUp()"
            @pointerleave="onPointerUp()"
        >
            {{ $slot }}
        </div>
    </div>
</section>

