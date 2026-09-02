{{--
  Where somebody is, which decides how every time on the screen reads.

  Three ways in, and all three are the same form posting one thing: a name from
  the search field, a name from the browser, or a point on the map. Picking is
  the save, as it is for the four format rows above, and for the same reason:
  every one of these is reversible by picking again.

  Every zone is in the page as a real submit button, all four hundred of them, so
  there is no separate search index to ship: the line a button is matched on rides
  on the button. Alpine hides what does not match, which is one handler writing an
  attribute rather than four hundred expressions each watching a variable.

  Arrowing moves real focus between real buttons, so Enter and Space are the
  browser's own and nothing here reimplements them.

  @var \App\ViewModels\Settings\Account\Preferences\TimezonePanel $timezone
--}}
<section class="flex flex-col gap-3">
  <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
    <h2 class="shrink-0 text-section-title text-ink">{{ __('Time zone') }}</h2>

    <p class="text-sm text-pretty text-muted">{{ __('Search for the city you are in, or the country, and pick it from the list.') }}</p>
  </div>

  <x-panel id="timezone-panel" :flush="true">
    <x-form
      method="put"
      :action="route('settings.timezone.update')"
      x-target="timezone-panel preview"
      x-data="{
        query: '',
        shown: 0,
        get needle() { return this.query.trim().toLowerCase() },
        filter() {
          const limit = 8;
          let shown = 0;
          this.$refs.results.querySelectorAll('[data-search]').forEach(row => {
            const hit = this.needle !== '' && row.dataset.search.includes(this.needle) && shown < limit;
            row.hidden = ! hit;
            if (hit) { shown++ }
          });
          this.shown = shown;
        },
        step(from, by) {
          const rows = [...this.$refs.results.querySelectorAll('[data-search]:not([hidden]) button')];
          if (rows.length === 0) { return }
          const at = rows.indexOf(from);
          rows[Math.min(rows.length - 1, Math.max(0, at + by))].focus();
        },
        reset() { this.query = ''; this.filter() },
        clear() { this.reset(); this.$refs.field.focus() },
        deviceSilent: false,
        {{--
          The browser's own answer, which is where sign up got the zone in the
          first place. It goes through the same field and the same rule as a name
          picked from the list, so a browser reporting a zone the product does not
          carry is refused by the endpoint rather than trusted by the screen.
        --}}
        device(event) {
          let zone = '';
          try { zone = Intl.DateTimeFormat().resolvedOptions().timeZone || '' } catch (e) {}
          if (zone === '') { this.deviceSilent = true; return }
          event.currentTarget.value = zone;
          this.$el.requestSubmit(event.currentTarget);
        },
        {{--
          A click becomes a latitude and a longitude and nothing more. Which zone
          that is belongs to the server, where working out the nearest of four
          hundred, across the antimeridian, is covered by a test.
        --}}
        point(event) {
          const box = event.currentTarget.getBoundingClientRect();
          if (box.width === 0 || box.height === 0) { return }
          this.$refs.longitude.value = ((event.clientX - box.left) / box.width * 360 - 180).toFixed(4);
          this.$refs.latitude.value = (84 - (event.clientY - box.top) / box.height * 142).toFixed(4);
          this.$el.requestSubmit();
        },
      }"
      x-init="filter()"
      {{--
        A pick is a save, and the morph that follows it keeps this component and
        everything in it, the query included, so the list would sit there open
        over the answer it just gave. Emptying the query as the request leaves is
        what closes it, and it is what `x-dropdown` does with its own panel for
        the same reason. Listened for on the window because the four format rows
        above aim at this panel too, and their saves should leave it settled as well.
      --}}
      @ajax:before.window="reset()"
      class="contents"
    >
      <div class="flex flex-wrap items-start gap-4 p-4">
        <div class="relative min-w-0 flex-1 basis-72 sm:max-w-96">
          <label class="flex h-9 items-center gap-2 rounded-md border border-hairline bg-input px-2.5 transition-colors duration-(--duration-fast) ease-out focus-within:border-hairline-strong focus-within:ring-[3px] focus-within:ring-focus-ring/45">
            <span class="sr-only">{{ __('Search a city or a country') }}</span>

            <x-icon name="search" :size="14" class="shrink-0 text-muted" />

            <input
              type="search"
              x-ref="field"
              x-model="query"
              @input="filter()"
              @keydown.arrow-down.prevent="step(null, 0)"
              @keydown.escape.prevent="clear()"
              placeholder="{{ __('Search a city or a country') }}"
              autocomplete="off"
              class="w-full min-w-0 border-0 bg-transparent p-0 font-sans text-[16px] text-ink placeholder-placeholder focus:outline-none sm:text-sm"
            />

            <button
              type="button"
              x-cloak
              x-show="needle !== ''"
              @click="clear()"
              class="flex size-5 shrink-0 items-center justify-center rounded-sm text-muted transition-colors hover:text-ink"
            >
              <span class="sr-only">{{ __('Clear the search') }}</span>

              <x-icon name="close" :size="12" :stroke-width="1.8" />
            </button>
          </label>

          <p x-cloak x-show="needle === ''" class="mt-1.5 text-xs text-muted">{{ __('Type a city, or a country to see its cities.') }}</p>

          {{--
            Hung over what follows rather than pushing it down, the way the
            dropdowns above this do, so the panel does not change height as
            somebody types.
          --}}
          <div
            x-ref="results"
            x-cloak
            x-show="needle !== ''"
            class="absolute inset-x-0 top-full z-10 mt-1.5 flex max-h-72 flex-col overflow-y-auto rounded-md border border-hairline bg-canvas shadow-pop"
          >
            @foreach ($timezone->zones() as $zone)
              <div data-search="{{ $zone['search'] }}" class="border-t border-hairline-soft first:border-t-0">
                <button
                  type="submit"
                  name="timezone"
                  value="{{ $zone['timezone'] }}"
                  @keydown.arrow-down.prevent="step($event.target, 1)"
                  @keydown.arrow-up.prevent="step($event.target, -1)"
                  @keydown.escape.prevent="clear()"
                  class="flex w-full items-center gap-3 px-3 py-2 text-left transition-colors hover:bg-hover focus-visible:bg-hover focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
                >
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm {{ $zone['selected'] ? 'font-semibold' : '' }} text-ink">{{ $zone['name'] }}</span>

                    <span class="block truncate text-xs text-muted">{{ $zone['timezone'] }}</span>
                  </span>

                  <span class="shrink-0 font-mono text-xs text-muted-soft">{{ $zone['offset'] }}</span>

                  <span class="w-12 shrink-0 text-right font-mono text-xs text-body">{{ $zone['clock'] }}</span>

                  @if ($zone['selected'])
                    <x-icon name="check" :size="14" :stroke-width="1.8" class="shrink-0 text-ink" />
                  @else
                    <span class="size-3.5 shrink-0" aria-hidden="true"></span>
                  @endif
                </button>
              </div>
            @endforeach

            <p x-cloak x-show="shown === 0" class="px-3 py-2 text-sm text-muted">{{ __('No city by that name. Try the country instead.') }}</p>
          </div>
        </div>

        <div class="ml-auto flex flex-col items-end gap-0.5 text-right">
          <span class="text-label tracking-caption text-muted-soft uppercase">{{ __('Your time zone') }}</span>

          <span class="font-mono text-sm text-ink">{{ $timezone->timezone() }}</span>

          <span class="text-xs text-muted">{{ __(':offset · :clock now', ['offset' => 'UTC' . $timezone->offset(), 'clock' => $timezone->clock()]) }}</span>
        </div>
      </div>

      {{--
        The aspect is the projection's own, so the drawing is never stretched and
        a fraction of the box is a fraction of the world. A click is the only way
        in here, which is why the search field above it is the one that has to be
        reachable from the keyboard: this is an addition to it, not a substitute.
      --}}
      <div
        @click="point($event)"
        role="presentation"
        class="relative aspect-[1000/394.44] w-full cursor-crosshair border-t border-b border-hairline-soft select-none"
      >
        <x-world-map />

        <input type="hidden" name="latitude" x-ref="latitude" />
        <input type="hidden" name="longitude" x-ref="longitude" />

        {{-- The hour somebody is in, fifteen degrees of it. --}}
        <span
          aria-hidden="true"
          style="left: {{ $timezone->band()['left'] }}%; width: {{ $timezone->band()['width'] }}%"
          class="pointer-events-none absolute inset-y-0 bg-map-band"
        ></span>

        {{--
          A halo, a ring and a dot. Three shapes, so the pin is found by its form
          and not only by being the one blue thing on the map.
        --}}
        <span
          aria-hidden="true"
          style="left: {{ $timezone->marker()['x'] }}%; top: {{ $timezone->marker()['y'] }}%"
          class="pointer-events-none absolute flex size-5 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-map-marker/20"
        >
          <span class="size-2.5 rounded-full border-2 border-map-water bg-map-marker"></span>
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-hairline bg-card px-4 py-3">
        <p class="min-w-0 flex-1 basis-64 text-sm text-pretty text-body">{{ $timezone->sentence() }}</p>

        {{--
          A zone reached by pointing is the nearest one to wherever the pointer
          landed, which is a guess. Said in words rather than shown by a tint,
          because somebody who cannot see the tint is the person most likely to
          have missed by a few degrees.
        --}}
        @if ($timezone->isGuess())
          <p class="shrink-0 text-xs text-muted-soft">{{ __('This is the nearest city to where you clicked.') }}</p>
        @endif

        <x-error :messages="$errors->get('timezone')" />
        <x-error :messages="$errors->get('latitude')" />

        <p x-cloak x-show="deviceSilent" class="text-xs text-error">{{ __('Your browser did not say where it is. Search for your city instead.') }}</p>

        <button
          type="submit"
          name="timezone"
          value=""
          @click.prevent="device($event)"
          class="ml-auto flex shrink-0 items-center gap-2 rounded-md border border-hairline bg-canvas px-3 py-1.5 text-sm text-ink transition-colors hover:bg-hover focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >
          <x-icon name="place" :size="14" :stroke-width="1.6" class="text-muted" />
          {{ __('Use my device') }}
        </button>
      </div>
    </x-form>
  </x-panel>
</section>
