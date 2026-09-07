{{-- One kind of link, on the list that groups and orders them. --}}
{{-- @var array $type --}}
<div data-row-id="{{ $type['id'] }}" class="relative border-b border-hairline last:border-b-0 {{ $type['isActive'] ? '' : 'bg-sunken' }}">
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
    class="flex items-center gap-3 py-3.5 pr-4 pl-2 transition-colors duration-(--duration-fast) ease-out hover:bg-hover"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder" aria-hidden="true">
      <x-icon name="grip" :size="15" :stroke-width="1.5" />
    </span>

    <div class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2.5 gap-y-1">
      <span class="shrink-0 text-base font-semibold text-ink">{{ $type['name'] }}</span>

      <span class="flex min-w-0 items-baseline gap-1.5 text-xs text-muted">
        <span class="truncate">{{ $type['roleA'] }}</span>
        <span aria-hidden="true">{{ $type['isSymmetric'] ? '↔' : '→' }}</span>
        <span class="truncate">{{ $type['roleB'] }}</span>
        <span class="sr-only">{{ $type['directionLabel'] }}</span>
      </span>

      @if ($type['note'])
        <span class="truncate text-xs text-muted">{{ $type['note'] }}</span>
      @endif

      @if ($type['isSystem'])
        <x-badge tone="info" class="shrink-0">{{ __('Supplied') }}</x-badge>
      @endif

      @unless ($type['isActive'])
        <x-badge tone="warning" class="shrink-0">{{ __('Not offered') }}</x-badge>
      @endunless
    </div>

    <div class="ml-auto flex shrink-0 items-center">
      <x-button.secondary
        size="small"
        :href="route('settings.relationshipTypes.edit', $type['id'])"
        data-turbo="true"
      >{{ __('Edit') }}</x-button.secondary>
    </div>
  </div>

  <x-form
    method="put"
    :action="route('settings.relationshipTypePosition.update', $type['id'])"
    x-target="relationship-types"
    :data-position-form="$type['id']"
    class="hidden"
  >
    <input type="hidden" name="position" value="" />
  </x-form>
</div>
