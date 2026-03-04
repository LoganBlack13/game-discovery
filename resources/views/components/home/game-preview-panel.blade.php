@props([])

{{-- Renders inside Alpine scope with previewOpen and previewGame (title, gameUrl, releaseDate, countdown, latestNewsTitle) --}}
<div
    x-show="previewOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="previewOpen = false"
    role="dialog"
    aria-modal="true"
    aria-label="Game preview"
>
    <div
        class="card w-full max-w-md bg-base-200 border border-base-content/10 shadow-xl rounded-box overflow-hidden"
        @click.stop
    >
        <div class="card-body gap-4">
            <div class="flex items-start justify-between gap-4">
                <h3 class="font-display text-xl font-semibold text-base-content" x-text="previewGame?.title ?? ''"></h3>
                <button
                    type="button"
                    class="btn btn-ghost btn-sm btn-circle shrink-0"
                    aria-label="Close"
                    @click="previewOpen = false"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <dl class="grid gap-2 text-sm">
                <div x-show="previewGame?.releaseDate">
                    <dt class="text-base-content/60">Release</dt>
                    <dd class="font-medium" x-text="previewGame?.releaseDate ?? ''"></dd>
                </div>
                <div x-show="previewGame?.countdown">
                    <dt class="text-base-content/60">Countdown</dt>
                    <dd class="font-medium" x-text="previewGame?.countdown ?? ''"></dd>
                </div>
                <div>
                    <dt class="text-base-content/60">Est. completion time</dt>
                    <dd class="font-medium">—</dd>
                </div>
                <div x-show="previewGame?.latestNewsTitle">
                    <dt class="text-base-content/60">Latest news</dt>
                    <dd class="font-medium line-clamp-2" x-text="previewGame?.latestNewsTitle ?? ''"></dd>
                </div>
            </dl>

            <div class="card-actions justify-end pt-2">
                <a
                    :href="previewGame?.gameUrl ?? '#'"
                    class="btn btn-primary btn-sm"
                    @click="previewOpen = false"
                >
                    View game
                </a>
            </div>
        </div>
    </div>
</div>
