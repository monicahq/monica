{{-- The vault's life events on the dashboard, one slice at a time beside the index of slices. --}}
{{-- @var \App\ViewModels\Vaults\DashboardTimelineBlock $timeline --}}
<section
  id="dashboard-timeline"
  aria-labelledby="dashboard-timeline-name dashboard-timeline-title"
  x-data
  x-on:ajax:merged.self="document.activeElement === document.body && document.getElementById('dashboard-timeline-title')?.focus()"
>
  <span id="dashboard-timeline-name" class="sr-only">{{ __('Timeline') }}</span>

  @if ($timeline->holdsNothing())
    <h2 id="dashboard-timeline-title" class="sr-only">{{ __('Nothing on the timeline yet') }}</h2>

    <x-panel :flush="true">
      <x-empty-state :title="__('Nothing on the timeline yet')">
        <x-slot:icon><x-icon name="timeline" :size="28" :stroke-width="1.5" /></x-slot:icon>
        {{ __('Life events written about anybody in this vault land here, what is coming up first.') }}
      </x-empty-state>
    </x-panel>
  @else
    <div class="grid items-start gap-7 md:grid-cols-[8.75rem_minmax(0,1fr)]">
      <nav aria-label="{{ __('Years') }}" class="-mx-1 flex gap-0.5 overflow-x-auto px-1 py-1 md:mx-0 md:flex-col md:overflow-visible md:p-0">
        @foreach ($timeline->index() as $slice)
          @if ($slice['startsYears'])
            <div class="mx-1 w-px shrink-0 bg-hairline md:mx-0 md:my-2 md:h-px md:w-auto" aria-hidden="true"></div>
          @endif

          <a
            href="{{ $slice['url'] }}"
            x-target.push.nofocus="dashboard-timeline"
            @if ($slice['isCurrent']) aria-current="page" @endif
            class="focus-ring flex h-8 shrink-0 items-center justify-between gap-3 rounded-md px-2.5 text-sm font-medium text-body transition-colors duration-(--duration-fast) hover:bg-hover hover:text-ink aria-[current=page]:bg-canvas aria-[current=page]:text-ink aria-[current=page]:shadow-nav-active"
          >
            {{ $slice['label'] }}
            <span class="text-xs font-normal text-muted tabular-nums" aria-hidden="true">{{ $slice['count'] }}</span>
            <span class="sr-only">{{ trans_choice(':count entry|:count entries', $slice['count']) }}</span>
          </a>
        @endforeach
      </nav>

      <div class="flex min-w-0 flex-col gap-4">
        <form
          method="get"
          action="{{ $timeline->filterUrl() }}"
          x-target.push.nofocus="dashboard-timeline"
          x-on:record-chosen="$nextTick(() => $el.requestSubmit())"
          class="flex flex-wrap items-center gap-2"
        >
          <input type="hidden" name="slice" value="{{ $timeline->selectedSlice() }}">

          <x-record-picker
            name="contact"
            id="dashboard-timeline-contact"
            :url="$timeline->contactSearchUrl()"
            :label="__('With')"
            :placeholder="__('With anyone')"
            :chosen="$timeline->contactChoice()['uuid'] ?? null"
            :chosen-name="$timeline->contactChoice()['name'] ?? null"
            :compact="true"
          />

          <x-select
            id="dashboard-timeline-type"
            name="type"
            size="small"
            aria-label="{{ __('Event type') }}"
            x-on:change="$el.form.requestSubmit()"
          >
            <option value="">{{ __('All event types') }}</option>
            @foreach ($timeline->typeOptions() as $category)
              <optgroup label="{{ $category['category'] }}">
                @foreach ($category['types'] as $type)
                  <option value="{{ $type['uuid'] }}" @selected($type['isSelected'])>{{ $type['name'] }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </x-select>

          <noscript>
            <x-button.secondary type="submit" size="small">{{ __('Filter') }}</x-button.secondary>
          </noscript>

          @if ($timeline->isFiltered())
            <x-link :href="$timeline->clearFiltersUrl()" x-target.push.nofocus="dashboard-timeline" class="px-1 text-sm">{{ __('Clear filters') }}</x-link>
          @endif
        </form>

        <h2 id="dashboard-timeline-title" tabindex="-1" class="flex items-baseline gap-2.5 rounded-xs focus-ring">
          <span class="text-xl font-semibold tracking-tight text-ink">{{ $timeline->title() }}</span>
          <span class="text-sm font-normal text-muted">{{ $timeline->countLabel() }}</span>
        </h2>

        <p aria-live="polite" class="sr-only">{{ $timeline->title() }}, {{ $timeline->countLabel() }}</p>

        @if ($timeline->isEmptySlice())
          <div class="flex flex-col gap-1 py-6">
            <p class="text-base font-semibold text-ink">{{ __('Nothing matches here') }}</p>
            <p class="text-sm text-body">{{ __('No life event in this period fits the filters. The counts beside the years show where they are.') }}</p>
          </div>
        @else
          <x-load-more
            rows-id="dashboard-timeline-rows"
            footer-id="dashboard-timeline-more"
            :next-page-url="$timeline->nextPageUrl()"
            :shows-end="$timeline->page() > 1"
            :count="collect($timeline->months())->sum(fn (array $month): int => count($month['entries']))"
            rows-class="flex flex-col gap-2"
          >
            @foreach ($timeline->months() as $month)
              <div id="dashboard-timeline-group-{{ $month['key'] }}-{{ $timeline->page() }}" x-data="{ open: true }" class="flex flex-col gap-0.5">
                @if ($month['showsHeading'])
                  <h3>
                    <button
                      type="button"
                      x-on:click="open = ! open"
                      x-bind:aria-expanded="open ? 'true' : 'false'"
                      aria-controls="dashboard-timeline-month-{{ $month['key'] }}-{{ $timeline->page() }}"
                      class="focus-ring -mx-1.5 flex items-center gap-2 rounded-md p-1.5 text-start hover:bg-hover"
                    >
                      <span class="flex text-muted" aria-hidden="true">
                        <x-icon name="chevron-down" :size="14" x-show="open" />
                        <x-icon name="chevron-right" :size="14" x-show="! open" x-cloak class="rtl:-scale-x-100" />
                      </span>
                      <x-caption tone="muted">{{ $month['label'] }}</x-caption>
                      <span class="text-xs text-muted tabular-nums" aria-hidden="true">{{ count($month['entries']) }}</span>
                      <span class="sr-only">{{ trans_choice(':count entry|:count entries', count($month['entries'])) }}</span>
                    </button>
                  </h3>
                @endif

                <ol
                  id="dashboard-timeline-month-{{ $month['key'] }}-{{ $timeline->page() }}"
                  x-show="open"
                  class="relative flex list-none flex-col before:absolute before:top-5 before:bottom-5 before:start-3.75 before:w-px before:bg-hairline"
                >
                  @foreach ($month['entries'] as $entry)
                    <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-x-3.5 py-2">
                      <span class="relative flex size-8 rounded-full bg-canvas" aria-hidden="true">
                        <span class="flex size-full items-center justify-center rounded-full border {{ $entry['tint'] }}">
                          <x-icon :name="$entry['icon']" :size="15" />
                        </span>
                      </span>

                      <div @if ($entry['isJustAdded']) tabindex="-1" x-autofocus @endif class="group -ms-2.5 flex min-w-0 items-start gap-2 rounded-lg focus-ring border border-transparent px-2.5 pt-1 pb-1.5 transition-colors motion-reduce:transition-none focus-within:border-hairline focus-within:bg-card hover:border-hairline hover:bg-card">
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                          <p class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <span class="text-base font-semibold text-ink">{{ $entry['title'] }}</span>

                            @if ($entry['typeName'] !== $entry['title'])
                              <span class="sr-only">{{ $entry['typeName'] }}</span>
                            @endif
                            <span class="text-sm text-muted">{{ $entry['date'] }}</span>

                            @if ($entry['relative'] !== null)
                              <span class="text-xs text-muted">{{ $entry['relative'] }}</span>
                            @endif

                            @if ($entry['isJustAdded'])
                              <x-badge>{{ __('Just added') }}</x-badge>
                            @endif
                          </p>

                          @if ($entry['with'] !== [])
                            @php
                              $names = collect($entry['with'])
                                ->map(fn (array $person): string => $person['url'] === null
                                  ? e($person['name'])
                                  : '<a href="'.e($person['url']).'" data-turbo="true" class="rounded-xs font-medium focus-ring text-link underline decoration-hairline-strong underline-offset-3 hover:decoration-ink">'.e($person['name']).'</a>')
                                ->implode(e(__(', ')));
                            @endphp

                            <p class="text-sm text-body">{!! __('With :names', ['names' => $names]) !!}</p>
                          @endif
                        </div>

                        @if ($entry['editUrl'] !== null)
                          <div class="flex shrink-0 transition-opacity motion-reduce:transition-none opacity-0 group-focus-within:opacity-100 group-hover:opacity-100 pointer-coarse:opacity-100">
                            <x-button.ghost :href="$entry['editUrl']" data-turbo="true" size="small" aria-label="{{ __('Edit :name', ['name' => $entry['title']]) }}">{{ __('Edit') }}</x-button.ghost>
                          </div>
                        @endif
                      </div>
                    </li>
                  @endforeach
                </ol>
              </div>
            @endforeach
          </x-load-more>
        @endif

        @if ($timeline->newer() !== null || $timeline->older() !== null)
          <div class="flex justify-between gap-3 border-t border-hairline-soft pt-4">
            @if ($timeline->newer() !== null)
              <x-button.secondary :href="$timeline->newer()['url']" x-target.push.nofocus="dashboard-timeline" size="small">{{ $timeline->newer()['label'] }}</x-button.secondary>
            @else
              <span></span>
            @endif

            @if ($timeline->older() !== null)
              <x-button.secondary :href="$timeline->older()['url']" x-target.push.nofocus="dashboard-timeline" size="small">{{ $timeline->older()['label'] }}</x-button.secondary>
            @endif
          </div>
        @endif
      </div>
    </div>
  @endif
</section>
