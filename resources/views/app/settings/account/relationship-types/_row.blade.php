{{-- One kind of link, on the list that groups and orders them. --}}
{{-- @var array $type --}}
<x-sortable-row
  :row-id="$type['id']"
  :action="route('settings.relationshipTypePosition.update', $type['id'])"
  target="relationship-types"
  :class="$type['isActive'] ? '' : 'bg-sunken'"
>
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
</x-sortable-row>
