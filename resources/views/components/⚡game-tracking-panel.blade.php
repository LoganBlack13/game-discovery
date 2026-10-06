<?php

use App\Enums\BacklogPriority;
use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use App\Http\Requests\UpdateTrackedGameRequest;
use App\Models\Game;
use App\Models\TrackedGame;
use App\Models\User;
use App\Services\PersonalTrackingService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $gameId;

    public ?string $pendingStatus = null;

    public ?string $reason = null;

    public string $comment = '';

    public bool $showReviewForm = false;

    public ?string $platform = null;

    public ?string $progress = null;

    public ?int $progressPercent = null;

    public ?string $notes = null;

    public ?string $priority = null;

    public bool $isUpNext = false;

    public ?int $rating = null;

    public ?string $wouldRecommend = null;

    public ?int $playtimeHours = null;

    public ?string $review = null;

    public string $journalBody = '';

    public bool $journalIsResumeGoal = false;

    public ?int $editingChangeId = null;

    public string $editingComment = '';

    public ?string $feedback = null;

    public function mount(Game $game): void
    {
        $this->gameId = $game->id;
        $this->fillFromEntry();
    }

    #[Computed]
    public function entry(): TrackedGame
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user->trackedGameEntries()
            ->with(['game', 'latestInterruption'])
            ->where('game_id', $this->gameId)
            ->firstOrFail();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrackedGameStatusChange>
     */
    #[Computed]
    public function statusChanges(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->entry->statusChanges()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry>
     */
    #[Computed]
    public function journalEntries(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->entry->journalEntries()->limit(20)->get();
    }

    public function selectStatus(string $value): void
    {
        $status = TrackedGameStatus::tryFrom($value);
        if ($status === null) {
            return;
        }

        if ($status->isInterruption()) {
            $this->pendingStatus = $status->value;
            $this->reason = null;
            $this->comment = '';

            return;
        }

        $this->applyStatus($status);
    }

    public function confirmInterruption(): void
    {
        $status = TrackedGameStatus::tryFrom((string) $this->pendingStatus);
        if ($status === null || ! $status->isInterruption()) {
            $this->cancelInterruption();

            return;
        }

        $this->validate(UpdateTrackedGameRequest::interruptionRules(), UpdateTrackedGameRequest::livewireMessages());

        $this->applyStatus(
            $status,
            $this->reason !== null && $this->reason !== '' ? InterruptionReason::from($this->reason) : null,
            $this->comment,
        );

        $this->cancelInterruption();
    }

    public function cancelInterruption(): void
    {
        $this->pendingStatus = null;
        $this->reason = null;
        $this->comment = '';
        $this->resetValidation(['reason', 'comment']);
    }

    public function saveDetails(): void
    {
        $this->normalizeEmptyStrings(['platform', 'progress', 'notes']);
        $this->validate(UpdateTrackedGameRequest::detailsRules(), UpdateTrackedGameRequest::livewireMessages());

        $this->entry->update([
            'platform' => $this->platform,
            'progress' => $this->progress,
            'progress_percent' => $this->progressPercent,
            'notes' => $this->notes,
            'last_activity_at' => now(),
        ]);

        $this->refreshEntry('Progress saved.');
    }

    public function saveBacklog(): void
    {
        $this->normalizeEmptyStrings(['priority']);
        $this->validate(UpdateTrackedGameRequest::backlogRules(), UpdateTrackedGameRequest::livewireMessages());

        $this->entry->update([
            'priority' => $this->priority,
            'is_up_next' => $this->isUpNext,
        ]);

        $this->refreshEntry('Backlog preferences saved.');
    }

    public function saveReview(): void
    {
        $this->normalizeEmptyStrings(['wouldRecommend', 'review']);
        $this->validate(UpdateTrackedGameRequest::reviewRules(), UpdateTrackedGameRequest::livewireMessages());

        $this->entry->update([
            'rating' => $this->rating,
            'would_recommend' => $this->wouldRecommend === null ? null : $this->wouldRecommend === 'yes',
            'playtime_hours' => $this->playtimeHours,
            'review' => $this->review,
        ]);

        $this->showReviewForm = false;
        $this->refreshEntry('Personal review saved.');
    }

    public function openReviewForm(): void
    {
        $this->showReviewForm = true;
    }

    public function skipReview(): void
    {
        $this->showReviewForm = false;
    }

    public function addJournalEntry(): void
    {
        $this->validate(UpdateTrackedGameRequest::journalRules(), UpdateTrackedGameRequest::livewireMessages());

        app(PersonalTrackingService::class)->addJournalEntry($this->entry, $this->journalBody, $this->journalIsResumeGoal);

        $this->journalBody = '';
        $this->journalIsResumeGoal = false;
        $this->refreshEntry('Journal entry added.');
    }

    public function completeResumeGoal(int $journalEntryId): void
    {
        $this->entry->journalEntries()->whereKey($journalEntryId)->firstOrFail()->update(['completed_at' => now()]);

        $this->refreshEntry('Resume goal marked as done.');
    }

    public function editComment(int $statusChangeId): void
    {
        $change = $this->entry->statusChanges()->whereKey($statusChangeId)->firstOrFail();
        $this->editingChangeId = $change->id;
        $this->editingComment = $change->comment ?? '';
    }

    public function saveComment(): void
    {
        $this->validate(['editingComment' => ['nullable', 'string', 'max:1000']]);

        $change = $this->entry->statusChanges()->whereKey((int) $this->editingChangeId)->firstOrFail();
        $comment = mb_trim($this->editingComment);
        $change->update(['comment' => $comment !== '' ? $comment : null]);

        $this->cancelCommentEdit();
        $this->refreshEntry('Comment updated.');
    }

    public function cancelCommentEdit(): void
    {
        $this->editingChangeId = null;
        $this->editingComment = '';
    }

    private function applyStatus(TrackedGameStatus $status, ?InterruptionReason $reason = null, ?string $comment = null): void
    {
        $change = app(PersonalTrackingService::class)->changeStatus($this->entry, $status, $reason, $comment);

        if ($change !== null && $status->isFinished()) {
            $this->showReviewForm = true;
        }

        $this->refreshEntry($change !== null ? 'Status updated to '.$status->label().'.' : null);
    }

    private function refreshEntry(?string $feedback = null): void
    {
        unset($this->entry, $this->statusChanges, $this->journalEntries);
        $this->fillFromEntry();
        $this->feedback = $feedback;
    }

    private function fillFromEntry(): void
    {
        $entry = $this->entry;

        $this->platform = $entry->platform;
        $this->progress = $entry->progress;
        $this->progressPercent = $entry->progress_percent;
        $this->notes = $entry->notes;
        $this->priority = $entry->priority?->value;
        $this->isUpNext = $entry->is_up_next;
        $this->rating = $entry->rating;
        $this->wouldRecommend = $entry->would_recommend === null ? null : ($entry->would_recommend ? 'yes' : 'no');
        $this->playtimeHours = $entry->playtime_hours;
        $this->review = $entry->review;
    }

    /**
     * @param  list<string>  $properties
     */
    private function normalizeEmptyStrings(array $properties): void
    {
        foreach ($properties as $property) {
            if (is_string($this->{$property}) && mb_trim($this->{$property}) === '') {
                $this->{$property} = null;
            }
        }
    }
};
?>

<section id="my-tracking" class="mt-10 border-t border-base-content/10 pt-10" aria-labelledby="my-tracking-title">
    @php
        $entry = $this->entry;
        $currentStatus = $entry->status;
        $pending = $pendingStatus !== null ? \App\Enums\TrackedGameStatus::tryFrom($pendingStatus) : null;
    @endphp
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="my-tracking-title" class="font-display text-lg font-semibold text-base-content">My tracking</h2>
        @if ($currentStatus)
            <span class="badge {{ $currentStatus->badgeClass() }}" data-current-status>{{ $currentStatus->label() }}</span>
        @else
            <span class="badge badge-ghost" data-current-status>No status</span>
        @endif
    </div>

    @if ($feedback)
        <p class="mt-3 text-sm text-success" role="status">{{ $feedback }}</p>
    @endif

    {{-- Status --}}
    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Personal status">
        @foreach (\App\Enums\TrackedGameStatus::cases() as $status)
            <button
                type="button"
                wire:key="status-{{ $status->value }}"
                wire:click="selectStatus('{{ $status->value }}')"
                @class([
                    'btn btn-sm',
                    'btn-primary' => $currentStatus === $status,
                    'btn-outline' => $currentStatus !== $status,
                ])
                aria-pressed="{{ $currentStatus === $status ? 'true' : 'false' }}"
            >{{ $status->label() }}</button>
        @endforeach
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
        <div>
            <dt class="text-base-content/60">Started</dt>
            <dd class="text-base-content">{{ $entry->started_at?->format('M j, Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-base-content/60">Last activity</dt>
            <dd class="text-base-content">{{ $entry->last_activity_at?->format('M j, Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-base-content/60">{{ $currentStatus === \App\Enums\TrackedGameStatus::Dropped ? 'Dropped on' : 'Finished' }}</dt>
            <dd class="text-base-content">{{ $entry->finished_at?->format('M j, Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-base-content/60">Progress</dt>
            <dd class="text-base-content">
                @if ($entry->progress_percent !== null)
                    {{ $entry->progress_percent }}%
                @endif
                {{ $entry->progress ?? ($entry->progress_percent === null ? '—' : '') }}
            </dd>
        </div>
    </dl>

    @if ($currentStatus?->isInterruption() && $entry->latestInterruption && ($entry->latestInterruption->reason || $entry->latestInterruption->comment))
        <div class="mt-4 rounded-box border border-warning/30 bg-warning/10 p-4 text-sm" data-interruption-context>
            <p class="font-medium text-base-content">
                Why it was {{ $currentStatus === \App\Enums\TrackedGameStatus::Paused ? 'paused' : 'dropped' }}
                @if ($entry->latestInterruption->reason)
                    : {{ $entry->latestInterruption->reason->label() }}
                @endif
            </p>
            @if ($entry->latestInterruption->comment)
                <p class="mt-1 whitespace-pre-wrap text-base-content/80">{{ $entry->latestInterruption->comment }}</p>
            @endif
        </div>
    @endif

    {{-- Pause / drop reason --}}
    @if ($pending)
        <form wire:submit="confirmInterruption" class="mt-4 rounded-box border border-base-content/10 bg-base-200 p-4" aria-label="{{ $pending->label() }} reason">
            <p class="font-medium text-base-content">
                {{ $pending === \App\Enums\TrackedGameStatus::Paused ? 'Pausing' : 'Dropping' }} this game — why? <span class="text-sm font-normal text-base-content/60">(optional)</span>
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (\App\Enums\InterruptionReason::cases() as $interruptionReason)
                    <label wire:key="reason-{{ $interruptionReason->value }}" class="cursor-pointer">
                        <input type="radio" wire:model="reason" value="{{ $interruptionReason->value }}" class="peer sr-only" />
                        <span class="badge badge-lg badge-outline peer-checked:badge-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary">{{ $interruptionReason->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('reason') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            <label for="interruption-comment" class="label label-text mt-3 font-medium">Comment</label>
            <textarea id="interruption-comment" wire:model="comment" rows="2" maxlength="1000" class="textarea textarea-bordered w-full" placeholder="Where were you, what blocked you…"></textarea>
            @error('comment') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Confirm: {{ $pending->label() }}</button>
                <button type="button" wire:click="cancelInterruption" class="btn btn-ghost btn-sm">Cancel</button>
            </div>
        </form>
    @endif

    {{-- Closing review --}}
    @if ($entry->isFinished())
        <div class="mt-6 rounded-box border border-base-content/10 p-4" aria-label="Personal review">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-display font-semibold text-base-content">My review</h3>
                @if (! $showReviewForm)
                    <button type="button" wire:click="openReviewForm" class="btn btn-ghost btn-xs">{{ $entry->hasReview() ? 'Edit review' : 'Write a review' }}</button>
                @endif
            </div>

            @if ($showReviewForm)
                <form wire:submit="saveReview" class="mt-3 grid gap-3 sm:grid-cols-3">
                    <p class="text-sm text-base-content/70 sm:col-span-3">Everything here is optional and private.</p>
                    <div>
                        <label for="review-rating" class="label label-text font-medium">Rating (1–10)</label>
                        <input id="review-rating" type="number" min="1" max="10" wire:model="rating" class="input input-bordered input-sm w-full" />
                        @error('rating') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="review-playtime" class="label label-text font-medium">Time played (hours)</label>
                        <input id="review-playtime" type="number" min="0" wire:model="playtimeHours" class="input input-bordered input-sm w-full" />
                        @error('playtimeHours') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="review-recommend" class="label label-text font-medium">Would recommend?</label>
                        <select id="review-recommend" wire:model="wouldRecommend" class="select select-bordered select-sm w-full">
                            <option value="">No opinion</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="review-text" class="label label-text font-medium">Final thoughts</label>
                        <textarea id="review-text" wire:model="review" rows="3" maxlength="5000" class="textarea textarea-bordered w-full"></textarea>
                        @error('review') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-3">
                        <button type="submit" class="btn btn-primary btn-sm">Save review</button>
                        <button type="button" wire:click="skipReview" class="btn btn-ghost btn-sm">Skip for now</button>
                    </div>
                </form>
            @elseif ($entry->hasReview())
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3" data-review-summary>
                    <div>
                        <dt class="text-base-content/60">Rating</dt>
                        <dd class="text-base-content">{{ $entry->rating !== null ? $entry->rating.'/10' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/60">Time played</dt>
                        <dd class="text-base-content">{{ $entry->playtime_hours !== null ? $entry->playtime_hours.' h' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/60">Recommend</dt>
                        <dd class="text-base-content">{{ $entry->would_recommend === null ? '—' : ($entry->would_recommend ? 'Yes' : 'No') }}</dd>
                    </div>
                    @if ($entry->review)
                        <div class="col-span-2 sm:col-span-3">
                            <dt class="text-base-content/60">Final thoughts</dt>
                            <dd class="whitespace-pre-wrap text-base-content">{{ $entry->review }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <p class="mt-2 text-sm text-base-content/60">No review yet — totally optional.</p>
            @endif
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Progress & notes --}}
        <form wire:submit="saveDetails" class="flex flex-col gap-3 rounded-box border border-base-content/10 p-4" aria-label="Progress and notes">
            <h3 class="font-display font-semibold text-base-content">Progress &amp; notes</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="tracking-platform" class="label label-text font-medium">My platform</label>
                    <select id="tracking-platform" wire:model="platform" class="select select-bordered select-sm w-full">
                        <option value="">Not set</option>
                        @foreach (array_unique(array_filter([...$entry->game->platforms, $platform])) as $gamePlatform)
                            <option value="{{ $gamePlatform }}">{{ $gamePlatform }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tracking-progress-percent" class="label label-text font-medium">Progress (%)</label>
                    <input id="tracking-progress-percent" type="number" min="0" max="100" wire:model="progressPercent" class="input input-bordered input-sm w-full" />
                    @error('progressPercent') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="tracking-progress" class="label label-text font-medium">Where I am</label>
                <input id="tracking-progress" type="text" wire:model="progress" maxlength="255" placeholder="e.g. Chapter 4, 12/20 shrines" class="input input-bordered input-sm w-full" />
                @error('progress') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tracking-notes" class="label label-text font-medium">Personal notes</label>
                <textarea id="tracking-notes" wire:model="notes" rows="2" maxlength="2000" class="textarea textarea-bordered w-full"></textarea>
                @error('notes') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm">Save progress</button>
            </div>
        </form>

        {{-- Backlog --}}
        <form wire:submit="saveBacklog" class="flex flex-col gap-3 rounded-box border border-base-content/10 p-4" aria-label="Backlog">
            <h3 class="font-display font-semibold text-base-content">Backlog</h3>
            <div>
                <label for="tracking-priority" class="label label-text font-medium">Priority</label>
                <select id="tracking-priority" wire:model="priority" class="select select-bordered select-sm w-full">
                    <option value="">No priority</option>
                    @foreach (\App\Enums\BacklogPriority::cases() as $backlogPriority)
                        <option value="{{ $backlogPriority->value }}">{{ $backlogPriority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" wire:model="isUpNext" class="checkbox checkbox-primary checkbox-sm" />
                <span class="label-text">Pin to “Play next”</span>
            </label>
            <p class="text-xs text-base-content/60">Starting the game removes it from “Play next” automatically.</p>
            <div>
                <button type="submit" class="btn btn-outline btn-sm">Save backlog</button>
            </div>
        </form>
    </div>

    {{-- Journal --}}
    <div class="mt-6 rounded-box border border-base-content/10 p-4" aria-label="Journal">
        <h3 class="font-display font-semibold text-base-content">Journal</h3>
        @if ($entry->currentResumeGoal)
            <p class="mt-2 rounded-box bg-primary/10 p-3 text-sm text-base-content" data-resume-goal>
                <span class="font-medium">Next time:</span> {{ $entry->currentResumeGoal->body }}
            </p>
        @endif
        <form wire:submit="addJournalEntry" class="mt-3 flex flex-col gap-2">
            <label for="journal-body" class="sr-only">New journal entry</label>
            <textarea id="journal-body" wire:model="journalBody" rows="2" maxlength="1000" class="textarea textarea-bordered w-full" placeholder="What happened this session? What do you want to do next time?"></textarea>
            @error('journalBody') <p class="text-sm text-error">{{ $message }}</p> @enderror
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="label cursor-pointer gap-2">
                    <input type="checkbox" wire:model="journalIsResumeGoal" class="checkbox checkbox-sm" />
                    <span class="label-text">This is my resume goal</span>
                </label>
                <button type="submit" class="btn btn-primary btn-sm">Add entry</button>
            </div>
        </form>

        @if ($this->journalEntries->isNotEmpty())
            <ol class="mt-4 flex flex-col gap-3" role="list">
                @foreach ($this->journalEntries as $journalEntry)
                    <li wire:key="journal-{{ $journalEntry->id }}" class="rounded-box bg-base-200 p-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <time datetime="{{ $journalEntry->written_at->toIso8601String() }}" class="text-xs text-base-content/60">{{ $journalEntry->written_at->format('M j, Y · H:i') }}</time>
                            @if ($journalEntry->is_resume_goal)
                                <span @class(['badge badge-sm', 'badge-primary' => $journalEntry->isOpenResumeGoal(), 'badge-ghost line-through' => ! $journalEntry->isOpenResumeGoal()])>Resume goal</span>
                            @endif
                        </div>
                        <p class="mt-1 whitespace-pre-wrap text-base-content">{{ $journalEntry->body }}</p>
                        @if ($journalEntry->isOpenResumeGoal())
                            <button type="button" wire:click="completeResumeGoal({{ $journalEntry->id }})" class="btn btn-ghost btn-xs mt-1">Mark goal as done</button>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- History --}}
    @if ($this->statusChanges->isNotEmpty())
        <div class="mt-6" aria-label="Status history">
            <h3 class="font-display font-semibold text-base-content">History</h3>
            <ol class="mt-3 flex flex-col gap-2" role="list">
                @foreach ($this->statusChanges as $change)
                    <li wire:key="change-{{ $change->id }}" class="flex flex-col gap-1 border-l-2 border-base-content/10 pl-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <time datetime="{{ $change->occurred_at->toIso8601String() }}" class="text-xs text-base-content/60">{{ $change->occurred_at->format('M j, Y') }}</time>
                            <span class="text-base-content">
                                {{ $change->from_status?->label() ?? 'No status' }} → <span class="font-medium">{{ $change->to_status?->label() ?? 'No status' }}</span>
                            </span>
                            @if ($change->reason)
                                <span class="badge badge-outline badge-sm">{{ $change->reason->label() }}</span>
                            @endif
                        </div>
                        @if ($editingChangeId === $change->id)
                            <form wire:submit="saveComment" class="flex flex-col gap-2">
                                <label for="edit-comment-{{ $change->id }}" class="sr-only">Comment</label>
                                <textarea id="edit-comment-{{ $change->id }}" wire:model="editingComment" rows="2" maxlength="1000" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                                @error('editingComment') <p class="text-sm text-error">{{ $message }}</p> @enderror
                                <div class="flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-xs">Save</button>
                                    <button type="button" wire:click="cancelCommentEdit" class="btn btn-ghost btn-xs">Cancel</button>
                                </div>
                            </form>
                        @else
                            @if ($change->comment)
                                <p class="whitespace-pre-wrap text-base-content/80">{{ $change->comment }}</p>
                            @endif
                            @if ($change->isInterruption())
                                <button type="button" wire:click="editComment({{ $change->id }})" class="btn btn-ghost btn-xs self-start">{{ $change->comment ? 'Edit comment' : 'Add comment' }}</button>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</section>
