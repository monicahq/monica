{{-- The shared history of a group: what it did together and what was noted about it, newest first. --}}
{{-- @var \App\ViewModels\Vaults\GroupHistoryBlock $history --}}
<section aria-labelledby="group-history-title" class="flex flex-col gap-3.5">
  <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
    <h2 id="group-history-title" class="text-section-title text-ink">{{ __('Shared history') }}</h2>
    <p class="text-xs text-pretty text-muted">{{ __('What the group did together and what you noted about it, newest first.') }}</p>
  </div>

  @if ($history->holdsNothing())
    <x-empty-state :title="__('Nothing written down yet')">
      {{ __('Activities the group did together and notes about it land here. An activity also appears on the page of each person who was there.') }}

      <x-slot:icon>
        <x-icon name="timeline" :size="24" :stroke-width="1.5" />
      </x-slot:icon>
    </x-empty-state>
  @else
    <ol class="relative list-none ps-8.5 before:absolute before:top-2 before:bottom-2 before:start-3 before:w-0.5 before:rounded-xs before:bg-hairline">

      @foreach ($history->entries() as $entry)
        <li class="relative flex flex-col gap-1 pb-6.5" @if ($entry['kind'] === 'activity' && $entry['isJustAdded']) id="group-history-just-added" @endif>
          <span aria-hidden="true" class="absolute top-0 -start-8.5 flex size-6.5 items-center justify-center rounded-full border border-hairline bg-card text-ink-soft ring-4 ring-page">
            <x-icon :name="$entry['icon']" :size="14" />
          </span>

          <p class="pt-1 text-xs text-muted">{{ $entry['kindLabel'] }} · {{ $entry['when'] }}</p>

          @if ($entry['kind'] === 'note' && $entry['isEditing'])
            <x-form method="put" :action="$entry['updateUrl']" class="flex flex-col gap-3">
              <x-textarea id="group-note-edit-{{ $entry['id'] }}" name="body" :label="__('The note')" :value="$errors->editNote->any() ? old('body', $entry['body']) : $entry['body']" :error="$errors->editNote->get('body')" required />

              <div class="flex items-center gap-2">
                <x-button size="small">{{ __('Save') }}</x-button>
                <x-button.secondary :href="$entry['cancelUrl']" data-turbo="true" size="small">{{ __('Cancel') }}</x-button.secondary>
              </div>
            </x-form>
          @else
            @if ($entry['title'] !== null)
              <p class="font-semibold text-pretty text-ink">{{ $entry['title'] }}</p>
            @endif

            @if ($entry['body'] !== null)
              <p class="max-w-reading text-pretty whitespace-pre-line text-body">{{ $entry['body'] }}</p>
            @endif

            @if ($entry['kind'] === 'activity' && $entry['participantLine'] !== null)
              <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-2">
                <span class="flex ps-1.25" aria-hidden="true">
                  @foreach ($entry['participants'] as $name)
                    <span class="-ms-1.25 flex rounded-full ring-2 ring-page"><x-contact-avatar :name="$name" :size="22" /></span>
                  @endforeach
                </span>

                <span class="text-sm text-pretty text-body">{{ $entry['participantLine'] }}</span>
              </div>

              <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted">
                <x-icon name="link" :size="13" />
                {{ __('Also on the page of each person who was there') }}
              </p>
            @endif

            <div class="mt-1 flex flex-wrap items-center gap-2">
              @if ($entry['editUrl'] !== null)
                <x-button.ghost
                  :href="$entry['editUrl']"
                  data-turbo="true"
                  size="small"
                  :aria-label="$entry['kind'] === 'note' ? __('Edit the note of :date', ['date' => $entry['when']]) : __('Edit :name of :date', ['name' => $entry['title'] ?? $entry['kindLabel'], 'date' => $entry['when']])"
                >{{ __('Edit') }}</x-button.ghost>
              @endif

              @if ($entry['kind'] === 'note')
                <x-delete-action
                  :title="__('Delete this note?')"
                  :confirm="__('Delete the note')"
                  :action="$entry['destroyUrl']"
                  :label="__('Delete')"
                  variant="ghost"
                  size="small"
                >
                  <p>{{ __('The note of :date is removed from the group. Nothing else changes.', ['date' => $entry['when']]) }}</p>
                </x-delete-action>
              @endif
            </div>
          @endif
        </li>
      @endforeach
    </ol>

    @if ($history->nextPageUrl() !== null)
      <x-link :href="$history->nextPageUrl()" :turbo="true">{{ __('Load more') }}</x-link>
    @endif
  @endif
</section>
