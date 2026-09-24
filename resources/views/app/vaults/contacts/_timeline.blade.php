{{-- The timeline of a contact: the life events they took part in, newest first, by year. --}}
{{--
  @var \App\ViewModels\Vaults\ContactTimelineBlock $timeline
  @var \App\ViewModels\Vaults\LifeEventFormBlock $form
--}}
<div id="contact-timeline-rows" x-merge="append" class="flex flex-col gap-6">
  @if ($timeline->holdsNothing())
    @php
      $creation = $timeline->creationEntry();
    @endphp

    <ol class="relative flex list-none flex-col gap-5 before:absolute before:top-8 before:bottom-4 before:left-3.75 before:w-px before:bg-hairline">
      <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-x-4">
        <span class="relative flex size-8 items-center justify-center rounded-full border border-hairline bg-card text-muted" aria-hidden="true">
          <x-icon name="profile" :size="15" />
        </span>

        <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1 pt-1.5 text-base">
          <span class="font-medium text-ink">{{ $creation['title'] ?? '' }}</span>
          <span class="text-sm text-muted">· {{ $creation['date'] ?? '' }}</span>
        </p>
      </li>

      <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-x-4">
        <span class="relative flex size-8 items-center justify-center rounded-full border border-dashed border-hairline-strong bg-canvas text-muted-soft" aria-hidden="true">
          <x-icon name="plus" :size="15" />
        </span>

        <div class="flex min-w-0 flex-col gap-3 rounded-lg border border-dashed border-hairline-strong p-4">
          <div class="flex flex-col gap-1">
            <p class="font-medium text-ink">{{ $timeline->emptyTitle() }}</p>
            <p class="text-sm text-pretty text-muted">{{ $timeline->emptyLine() }}</p>
          </div>

          <div>
            <x-button.secondary type="button" size="small" x-on:click="show()" x-bind:disabled="open">
              <x-slot:icon><x-icon name="plus" :size="15" /></x-slot:icon>
              {{ __('Add the first life event') }}
            </x-button.secondary>
          </div>
        </div>
      </li>
    </ol>
  @endif

  @foreach ($timeline->years() as $year)
    <div class="flex flex-col gap-2">
      @if ($year['showsHeading'])
        <h3 class="flex items-baseline gap-2.5 pl-10">
          <span class="text-card-title text-ink">{{ $year['year'] }}</span>
          <span class="text-xs text-muted">{{ $year['countLabel'] }}</span>
        </h3>
      @endif

      <ol class="relative flex list-none flex-col before:absolute before:top-5 before:bottom-5 before:left-3.75 before:w-px before:bg-hairline">
        @foreach ($year['entries'] as $entry)
          <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-x-4 py-2">
            <span class="relative flex size-8 items-center justify-center rounded-full border border-hairline bg-card text-muted" aria-hidden="true">
              <x-icon :name="$entry['icon']" :size="15" />
            </span>

            @if ($entry['isEditing'])
              <div class="min-w-0">
                @include('app.vaults.contacts._life-event-panel', ['form' => $form])
              </div>
            @else
              <div @if ($entry['isJustAdded'] || $entry['isEdited']) tabindex="-1" x-autofocus @endif class="group -ml-2.5 flex min-w-0 flex-col rounded-lg border px-2.5 pt-1.5 pb-2 transition-colors motion-reduce:transition-none border-transparent focus-within:border-hairline focus-within:bg-card hover:border-hairline hover:bg-card">
                <div class="flex items-start gap-2">
                  <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-base">
                      <span class="font-medium text-ink">{{ $entry['title'] }}</span>
                      <span class="text-sm text-muted">· {{ $entry['date'] }}</span>

                      @if ($entry['isJustAdded'])
                        <x-badge>{{ __('Just added') }}</x-badge>
                      @endif

                      @if ($entry['isEdited'])
                        <x-badge>{{ __('Edited') }}</x-badge>
                      @endif
                    </p>

                    @if ($entry['details'] !== [])
                      <p class="text-sm text-pretty text-muted">{{ implode(' · ', $entry['details']) }}</p>
                    @endif

                    @if ($entry['narrative'])
                      <p class="text-sm text-pretty text-muted">{{ $entry['narrative'] }}</p>
                    @endif

                    @if ($entry['with'] !== [])
                      @php
                        $names = collect($entry['with'])
                          ->map(fn (array $person): string => $person['url'] === null
                            ? e($person['name'])
                            : '<a href="'.e($person['url']).'" class="font-medium text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">'.e($person['name']).'</a>')
                          ->implode(e(__(', ')));
                      @endphp

                      <p class="text-sm text-muted">{!! __('With :names', ['names' => $names]) !!}</p>
                    @endif
                  </div>

                  <div class="flex shrink-0 gap-0.5 transition-opacity motion-reduce:transition-none opacity-0 group-focus-within:opacity-100 group-hover:opacity-100 pointer-coarse:opacity-100">
                    <x-button.ghost :href="$entry['editUrl']" size="small" x-target="contact-timeline" aria-label="{{ __('Edit :name', ['name' => $entry['title']]) }}">{{ __('Edit') }}</x-button.ghost>

                    <x-modal.warning
                      :title="__('Delete this life event?')"
                      :confirm="__('Delete life event')"
                      :action="$entry['destroyUrl']"
                      x-target="contact-timeline"
                    >
                      <x-slot:trigger>
                        <x-button.ghost
                          type="button"
                          size="small"
                          @click="open = true"
                          class="hover:text-error"
                          aria-label="{{ __('Delete :name', ['name' => $entry['title']]) }}"
                        >{{ __('Delete') }}</x-button.ghost>
                      </x-slot:trigger>

                      <p>{{ $entry['deleteLine'] }}</p>
                    </x-modal.warning>
                  </div>
                </div>
              </div>
            @endif
          </li>
        @endforeach
      </ol>
    </div>
  @endforeach

  @if (! $timeline->holdsNothing() && ($creation = $timeline->creationEntry()))
    <ol class="relative flex list-none flex-col">
      <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-x-4 py-2">
        <span class="relative flex size-8 items-center justify-center rounded-full border border-hairline bg-card text-muted" aria-hidden="true">
          <x-icon name="profile" :size="15" />
        </span>

        <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1 pt-1.5 text-base">
          <span class="font-medium text-ink">{{ $creation['title'] }}</span>
          <span class="text-sm text-muted">· {{ $creation['date'] }}</span>
        </p>
      </li>
    </ol>
  @endif
</div>

<div id="contact-timeline-pagination">
  @if ($timeline->nextPageUrl())
    <x-link :href="$timeline->nextPageUrl()" x-target="contact-timeline-rows contact-timeline-pagination">{{ __('Load more') }}</x-link>
  @endif
</div>
