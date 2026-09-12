{{-- One kind of record, on the list that orders them. --}}
{{-- @var array $type --}}
<x-sortable-row
  :row-id="$type['id']"
  :action="route('settings.entityTypePosition.update', $type['slug'])"
  target="entity-types"
>
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
</x-sortable-row>
