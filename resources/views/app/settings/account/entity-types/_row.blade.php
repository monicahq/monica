{{--
  One kind of record, on the list that orders them.

  The row is draggable as a whole rather than by the handle alone, so the whole
  height of it is a target. The attribute is written out rather than bound, so
  the row is draggable from the first paint. It keeps a hover tint even though it
  is not a link, because it is the one row in the application that responds to
  being pushed around.

  The hidden form at the foot is what the drag saves through. It is here rather
  than in the sortable component so that the csrf token, the method spoofing
  and the ajax target are written the way every other form on the screen does.

  @var array $type
--}}
<div data-row-id="{{ $type['id'] }}" class="relative border-b border-hairline last:border-b-0">
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
    class="erica-row border-b-0 pl-2 transition-colors hover:bg-hover"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder" aria-hidden="true">
      <x-icon name="menu" :size="15" :stroke-width="1.5" />
    </span>

    <span
      class="erica-row__mark"
      style="color: {{ $type['colour'] }}; background: {{ $type['tint'] }}"
      aria-hidden="true"
    >
      <x-entity-icon :name="$type['icon']" :size="18" />
    </span>

    <div class="erica-row__main">
      <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="erica-row__title">{{ $type['nameSingular'] }}</span>

        <span class="erica-caption">{{ $type['namePlural'] }}</span>

        @unless ($type['isActive'])
          <x-badge tone="outline">{{ __('Not active') }}</x-badge>
        @endunless
      </p>

      <p class="truncate font-mono text-[12.5px] text-muted">{{ $type['slug'] }}</p>
    </div>

    <div class="erica-row__end">
      <span class="erica-caption">{{ $type['countLabel'] }}</span>

      <x-button.ghost
        size="small"
        :href="route('settings.entityTypes.edit', $type['slug'])"
        data-turbo="true"
      >{{ __('Edit') }}</x-button.ghost>
    </div>
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
