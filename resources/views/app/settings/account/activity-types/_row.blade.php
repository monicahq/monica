{{-- One nature an activity can be recorded as, on the list of the category it is filed under. --}}
{{--
  @var array $type
  @var bool $showsEveryType
--}}
<x-sortable-row
  :row-id="$type['id']"
  :action="route('settings.activityTypePosition.update', $type['id'])"
  target="activity-types"
  :class="$type['isActive'] ? '' : 'bg-sunken'"
>
  <x-section-mark :icon="$type['icon']" :colour="$type['colour']" :size="30" />

  <div class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2.5 gap-y-1">
    <span class="shrink-0 text-base font-semibold text-ink">{{ $type['name'] }}</span>

    @if ($type['description'])
      <span class="min-w-0 truncate text-xs text-muted">{{ $type['description'] }}</span>
    @endif

    @if ($type['category'])
      <span class="shrink-0 text-xs text-muted">{{ $type['category'] }}</span>
    @endif

    @if ($type['isSystem'])
      <x-badge tone="info" class="shrink-0">{{ __('Supplied') }}</x-badge>
    @endif

    @unless ($type['isActive'])
      <x-badge tone="warning" class="shrink-0">{{ __('Not offered') }}</x-badge>
    @endunless
  </div>

  <div class="ml-auto flex shrink-0 items-center gap-1.5">
    @unless ($showsEveryType)
      @unless ($type['isFirst'])
        <x-form method="put" :action="route('settings.activityTypePosition.update', $type['id'])" x-target="activity-types">
          <input type="hidden" name="position" value="{{ $type['position'] - 1 }}" />

          <x-icon-button
            type="submit"
            icon="chevron-up"
            :label="__('Move :name above :other', ['name' => $type['name'], 'other' => $type['previous']])"
          />
        </x-form>
      @endunless

      @unless ($type['isLast'])
        <x-form method="put" :action="route('settings.activityTypePosition.update', $type['id'])" x-target="activity-types">
          <input type="hidden" name="position" value="{{ $type['position'] + 1 }}" />

          <x-icon-button
            type="submit"
            icon="chevron-down"
            :label="__('Move :name below :other', ['name' => $type['name'], 'other' => $type['next']])"
          />
        </x-form>
      @endunless
    @endunless

    <x-button.secondary size="small" :href="$type['url']" data-turbo="true">{{ __('Edit') }}</x-button.secondary>
  </div>
</x-sortable-row>
