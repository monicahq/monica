{{--
  One kind of record, on the list that orders them.

  The row is draggable as a whole rather than by the handle alone, so the whole
  height of it is a target. The attribute is written out rather than bound, so
  the row is draggable from the first paint.

  The hidden form at the foot is what the drag saves through. It is here rather
  than in the sortable component so that the csrf token, the method spoofing
  and the ajax target are written the way every other form on the screen does.

  @var array $type
--}}
<div
  data-row-id="{{ $type['id'] }}"
  class="relative border-b border-hairline-soft transition-colors duration-150 first:rounded-t-xl last:rounded-b-xl last:border-b-0 hover:bg-hover"
>
  {{--
    The line showing where the dragged row would land. It is taken out of the
    flow, since a row that grows by two pixels under the pointer moves the row
    the pointer is on and the drag flickers between the two.
  --}}
  <div
    x-cloak
    x-show="hoveringId === {{ $type['id'] }} && draggingId !== {{ $type['id'] }}"
    class="absolute inset-x-0 top-0 z-10 h-0.5 bg-brand"
    aria-hidden="true"
  ></div>

  <div
    draggable="true"
    @dragstart="start($event, {{ $type['id'] }})"
    @dragover.prevent="over($event, {{ $type['id'] }})"
    @drop.prevent="drop($event, {{ $type['id'] }})"
    @dragend="stop()"
    :class="draggingId === {{ $type['id'] }} && 'opacity-40'"
    class="flex min-h-11 flex-wrap items-center gap-3 py-3 pr-4 pl-2"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder" aria-hidden="true">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <line x1="4" y1="5" x2="12" y2="5"></line>
        <line x1="4" y1="8" x2="12" y2="8"></line>
        <line x1="4" y1="11" x2="12" y2="11"></line>
      </svg>
    </span>

    <span
      style="color: {{ $type['colour'] }}; background: {{ $type['tint'] }}"
      class="flex size-7 shrink-0 items-center justify-center rounded-md"
      aria-hidden="true"
    >
      <x-entity-icon :name="$type['icon']" />
    </span>

    <div class="min-w-0 flex-1 space-y-0.5">
      <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="text-sm font-semibold text-ink">{{ $type['nameSingular'] }}</span>

        <span class="text-xs text-muted-soft">{{ $type['namePlural'] }}</span>

        @unless ($type['isActive'])
          <span class="rounded-sm border border-dashed border-hairline-strong px-1.5 py-px text-xs text-warning">{{ __('Not active') }}</span>
        @endunless
      </div>

      <p class="truncate font-mono text-xs text-muted-soft">{{ $type['slug'] }}</p>
    </div>

    <span class="shrink-0 text-xs text-muted">{{ $type['countLabel'] }}</span>

    <a
      href="{{ route('settings.entityTypes.edit', $type['slug']) }}"
      data-turbo="true"
      class="shrink-0 cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
    >{{ __('Edit') }}</a>
  </div>

  <x-form
    method="put"
    :action="route('settings.entityTypePosition.update', $type['slug'])"
    x-target="entity-types"
    :data-position-form="$type['id']"
    class="hidden"
  >
    <input type="hidden" name="position" value="" />
  </x-form>
</div>
