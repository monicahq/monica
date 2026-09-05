{{-- One kind of record, on the list that orders them. --}}
{{-- @var array $type --}}
<div data-row-id="{{ $type['id'] }}" class="relative border-b border-hairline last:border-b-0">
  <div
    x-cloak
    x-show="hoveringId === {{ $type['id'] }} && draggingId !== {{ $type['id'] }}"
    class="absolute inset-x-0 top-0 z-10 h-0.5 bg-primary"
    aria-hidden="true"
  ></div>

  <div
    draggable="true"
    @dragstart="start($event, {{ $type['id'] }})"
    @dragover.prevent="over($event, {{ $type['id'] }})"
    @drop.prevent="drop($event, {{ $type['id'] }})"
    @dragend="stop()"
    :class="draggingId === {{ $type['id'] }} && 'opacity-40'"
    class="flex w-full items-center gap-3 py-3.5 pr-4 pl-2 text-left transition-colors duration-(--duration-fast) ease-out hover:bg-hover"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder" aria-hidden="true">
      <x-icon name="menu" :size="15" :stroke-width="1.5" />
    </span>

    <span
      class="flex size-8 shrink-0 items-center justify-center rounded-sm"
      style="color: {{ $type['colour'] }}; background: {{ $type['tint'] }}"
      aria-hidden="true"
    >
      <x-entity-icon :name="$type['icon']" :size="18" />
    </span>

    <div class="min-w-0 flex-1">
      <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="text-base font-semibold text-ink">{{ $type['nameSingular'] }}</span>

        <span class="text-xs text-pretty text-muted">{{ $type['namePlural'] }}</span>

        @unless ($type['isActive'])
          <x-badge tone="outline">{{ __('Not active') }}</x-badge>
        @endunless
      </p>

      <p class="truncate font-mono text-[12.5px] text-muted">{{ $type['slug'] }}</p>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      <span class="text-xs text-pretty text-muted">{{ $type['countLabel'] }}</span>

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
