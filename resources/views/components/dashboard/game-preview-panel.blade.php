@props([])

{{--
    Game preview panel (slide-over). Expects parent Alpine scope with:
    - previewOpen (boolean)
    - previewGame (object|null) with: id, title, cover_image, release_date_iso, release_date_formatted, time_to_beat, latest_news_title, game_url
--}}
<div
    x-show="previewOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex justify-end"
    aria-modal="true"
    role="dialog"
    aria-labelledby="game-preview-title"
    x-bind:aria-hidden="!previewOpen"
    @keydown.escape.window="if (previewOpen) previewOpen = false"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-base-content/20 backdrop-blur-sm"
        x-show="previewOpen"
        x-transition
        @click="previewOpen = false"
    ></div>

    {{-- Slide-over panel --}}
    <div
        x-show="previewOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="relative z-10 flex w-full max-w-md flex-col border-l border-base-content/10 bg-base-100 shadow-2xl"
        @click.outside="previewOpen = false"
    >
        <div class="flex flex-col overflow-y-auto p-6" x-ref="panelContent">
            <button
                type="button"
                class="btn btn-ghost btn-sm btn-circle absolute right-4 top-4"
                aria-label="Close preview"
                @click="previewOpen = false"
            >
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <template x-if="previewGame">
                <div class="space-y-4">
                    <div class="aspect-[3/4] w-full overflow-hidden rounded-box bg-base-200">
                        <img
                            x-show="previewGame.cover_image"
                            x-bind:src="previewGame.cover_image"
                            alt=""
                            class="size-full object-cover"
                        />
                        <div
                            x-show="!previewGame.cover_image"
                            class="flex size-full items-center justify-center"
                        >
                            <span
                                class="font-display text-4xl font-bold text-base-content/40"
                                x-text="previewGame.title ? previewGame.title.charAt(0) : ''"
                            ></span>
                        </div>
                    </div>
                    <h2 id="game-preview-title" class="font-display text-xl font-semibold text-base-content" x-text="previewGame.title"></h2>
                    <div class="space-y-1 text-sm text-base-content/80">
                        <p x-show="previewGame.release_date_formatted">
                            <span x-text="previewGame.release_date_formatted || ''"></span>
                            <span x-show="previewGame.countdown_text" class="ml-1 font-mono tabular-nums text-primary" x-text="' — ' + (previewGame.countdown_text || '')"></span>
                        </p>
                        <p>
                            <span class="text-base-content/60">Estimated completion:</span>
                            <span x-text="previewGame.time_to_beat != null ? previewGame.time_to_beat + ' h' : '—'"></span>
                        </p>
                        <p x-show="previewGame.latest_news_title" class="line-clamp-2" x-text="'Latest: ' + (previewGame.latest_news_title || '')"></p>
                    </div>
                    <a
                        x-bind:href="previewGame.game_url"
                        class="btn btn-primary btn-sm"
                    >
                        View game
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>
