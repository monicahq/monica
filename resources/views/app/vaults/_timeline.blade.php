{{-- The vault's life events on the dashboard, one period at a time, narrowed by the filters above it. --}}
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
        {{ __('Life events written about anybody in this vault land here.') }}
      </x-empty-state>
    </x-panel>
  @else
    <div class="flex min-w-0 flex-col gap-4">
      <form
        method="get"
        action="{{ $timeline->filterUrl() }}"
        x-target.push.nofocus="dashboard-timeline"
        x-on:record-chosen="$nextTick(() => $el.requestSubmit())"
        class="flex flex-col gap-2"
      >
        <div class="grid grid-cols-[repeat(auto-fit,minmax(11.25rem,1fr))] gap-2">
          <x-select
            id="dashboard-timeline-period"
            name="slice"
            size="small"
            aria-label="{{ __('Period') }}"
            x-on:change="$el.form.requestSubmit()"
          >
            @foreach ($timeline->index() as $slice)
              @continue($slice['isYear'])
              <option value="{{ $slice['slice'] }}" @selected($slice['isCurrent'])>{{ $slice['label'] }} · {{ trans_choice(':count entry|:count entries', $slice['count']) }}</option>
            @endforeach

            @if (collect($timeline->index())->contains('isYear', true))
              <optgroup label="{{ __('Years') }}">
                @foreach ($timeline->index() as $slice)
                  @continue(! $slice['isYear'])
                  <option value="{{ $slice['slice'] }}" @selected($slice['isCurrent'])>{{ $slice['label'] }} · {{ trans_choice(':count entry|:count entries', $slice['count']) }}</option>
                @endforeach
              </optgroup>
            @endif
          </x-select>

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
        </div>

        <noscript>
          <x-button.secondary type="submit" size="small">{{ __('Filter') }}</x-button.secondary>
        </noscript>

        @if ($timeline->isFiltered())
          <x-link :href="$timeline->clearFiltersUrl()" x-target.push.nofocus="dashboard-timeline" class="self-start px-1 text-sm">{{ __('Clear filters') }}</x-link>
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
          <p class="text-sm text-body">{{ __('No life event in this period fits the filters. Choose another period: the counts beside each one show where they are.') }}</p>
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
                    <span class="text-xs text-muted tabular-nums" aria-hidden="true">({{ count($month['entries']) }})</span>
                    <span class="sr-only">{{ trans_choice(':count entry|:count entries', count($month['entries'])) }}</span>
                  </button>
                </h3>
              @endif

              <ol
                id="dashboard-timeline-month-{{ $month['key'] }}-{{ $timeline->page() }}"
                x-show="open"
                class="flex list-none flex-col"
              >
                @foreach ($month['entries'] as $entry)
                  <li
                    @if ($entry['isJustAdded']) tabindex="-1" x-autofocus @endif
                    x-data="{
                      open: false,
                      loaded: false,
                      loading: false,
                      failed: null,
                      detailsUrl: @js($entry['detailsUrl']),
                      detailsId: @js($entry['detailsId']),
                      load() {
                        this.loading = true;
                        this.failed = null;
                        this.$ajax(this.detailsUrl, { target: this.detailsId, focus: false })
                          .catch(() => this.fail(0))
                          .finally(() => this.loading = false);
                      },
                      fail(status) {
                        this.failed = status;
                        document.getElementById(this.detailsId)?.removeAttribute('aria-busy');
                      },
                    }"
                    x-on:ajax:error.stop="fail($event.detail.status)"
                    x-on:ajax:missing.stop.prevent
                    class="relative grid grid-cols-[5.5rem_2rem_minmax(0,1fr)] gap-x-2.5 rounded-xs focus-ring border-b border-hairline-soft py-3 last:border-b-0 sm:gap-x-3.5"
                  >
                    <p class="flex min-w-0 flex-col gap-0.5 pt-1">
                      <span class="text-sm font-semibold text-ink tabular-nums">{{ $entry['date'] }}</span>

                      @if ($entry['relative'] !== null)
                        <span class="text-xs text-muted">{{ $entry['relative'] }}</span>
                      @endif
                    </p>

                    <span class="flex size-8 rounded-full bg-canvas" aria-hidden="true">
                      <span class="flex size-full items-center justify-center rounded-full border {{ $entry['tint'] }}">
                        <x-icon :name="$entry['icon']" :size="15" />
                      </span>
                    </span>

                    <div class="group -ms-2.5 flex min-w-0 flex-col items-start gap-2 rounded-lg sm:flex-row border border-transparent px-2.5 pt-1 pb-1.5 transition-colors motion-reduce:transition-none focus-within:border-hairline focus-within:bg-card hover:border-hairline hover:bg-card">
                      <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <p class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                          <span class="text-base font-semibold text-ink">{{ $entry['title'] }}</span>

                          @if ($entry['typeName'] !== $entry['title'])
                            <span class="sr-only">{{ $entry['typeName'] }}</span>
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

                      <div
                        x-bind:data-open="open"
                        class="flex shrink-0 flex-wrap gap-1 sm:self-center opacity-0 transition-opacity motion-reduce:transition-none data-open:opacity-100 group-focus-within:opacity-100 group-hover:opacity-100 pointer-coarse:opacity-100"
                      >
                        <x-button.secondary
                          type="button"
                          size="small"
                          x-ref="detailsToggle"
                          aria-expanded="false"
                          aria-controls="{{ $entry['detailsId'] }}"
                          x-bind:aria-expanded="open.toString()"
                          x-on:click="open = ! open; if (open && ! loaded) { loaded = true; load() }"
                        >
                          <span x-text="open ? @js(__('Hide details')) : @js(__('Details'))">{{ __('Details') }}</span>
                          <span class="sr-only">{{ $entry['title'] }}, {{ $entry['date'] }}</span>
                          <x-icon name="chevron-down" :size="14" x-bind:class="open && 'rotate-180'" class="motion-safe:transition-transform" />
                        </x-button.secondary>

                        @if ($entry['editUrl'] !== null)
                          <x-button.secondary :href="$entry['editUrl']" data-turbo="true" size="small" aria-label="{{ __('Edit :name', ['name' => $entry['title']]) }}">{{ __('Edit') }}</x-button.secondary>
                        @endif
                      </div>
                    </div>

                    <p
                      role="status"
                      class="sr-only"
                      x-text="loading ? @js(__('Loading the details')) : (failed === null ? '' : (failed === 404 ? @js(__('This life event no longer exists.')) : @js(__('The details could not be loaded.'))))"
                    ></p>

                    <div x-show="open" x-cloak class="col-span-full pt-2 sm:col-span-1 sm:col-start-3">
                      <div id="{{ $entry['detailsId'] }}">
                        <div x-show="failed === null" class="flex flex-col gap-2.5 rounded-lg border border-hairline p-4 motion-safe:animate-pulse">
                          <span class="h-2.5 w-2/5 rounded-sm bg-hover" aria-hidden="true"></span>
                          <span class="h-2.5 w-4/5 rounded-sm bg-hover" aria-hidden="true"></span>
                          <span class="h-2.5 w-3/5 rounded-sm bg-hover" aria-hidden="true"></span>
                        </div>

                        <div x-show="failed !== null" x-cloak class="flex flex-wrap items-center gap-3 rounded-lg border border-hairline p-4 text-sm text-body">
                          <span x-show="failed === 404">{{ __('This life event no longer exists.') }}</span>
                          <span x-show="failed !== 404">{{ __('The details could not be loaded.') }}</span>

                          <x-button.secondary
                            type="button"
                            size="small"
                            x-show="failed !== 404"
                            x-on:click="$refs.detailsToggle.focus(); load()"
                          >{{ __('Try again') }}</x-button.secondary>
                        </div>
                      </div>
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
  @endif
</section>
