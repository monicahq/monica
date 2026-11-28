{{-- One value inside a list, either read or being renamed. --}}
{{--
  @var array $value
  @var string $target
--}}
@php
  $valueErrors = $errors->getBag('value'.$value['id']);
  $isEditing = $value['isEditing'] || $valueErrors->has('label') || $valueErrors->has('country_code');
  $rowId = 'value-'.$value['id'];
@endphp

<x-sortable-row
  :row-id="$value['id']"
  :action="route('settings.valueListItemPosition.update', $value['id'])"
  :target="$target"
  padding="py-2 pe-4 ps-2"
  :dragstart="$isEditing ? '$event.preventDefault()' : null"
  :id="$rowId"
  x-merge="replace"
>
  @if ($isEditing)
    <div class="min-w-0 flex-1">
      <x-form method="put" :action="route('settings.valueListItems.update', $value['id'])" :x-target="$target" class="flex flex-wrap items-start gap-3">
        @if ($value['holdsACountry'])
          <x-country-picker
            :id="'value-'.$value['id'].'-country-code'"
            name="country_code"
            typed-name="label"
            :label="__('Country')"
            :extras="$value['writtenLabel'] === null ? [] : [['value' => 'written', 'label' => $value['writtenLabel'], 'submits' => $value['countryCode'] ?? '', 'typed' => $value['writtenLabel']]]"
            :value="$value['writtenLabel'] === null ? ($value['countryCode'] ?? '') : 'written'"
            :hint="__('Pick a country, or type a name for a place not in the list.')"
            :error="[...$valueErrors->get('country_code'), ...$valueErrors->get('label')]"
            class="w-full max-w-80"
            autofocus
          />
        @else
          <x-input
            :id="'value-'.$value['id'].'-label'"
            name="label"
            size="small"
            :value="$value['label']"
            :aria-label="__('Name of the value')"
            :error="$valueErrors->get('label')"
            class="max-w-80"
            maxlength="255"
            required
            x-autofocus
          />
        @endif

        <div class="flex items-center gap-2">
          <x-button size="small">{{ __('Save') }}</x-button>

          <x-button.ghost size="small" :href="route('settings.valueLists.index')" :x-target="$rowId">{{ __('Cancel') }}</x-button.ghost>
        </div>
      </x-form>
    </div>
  @else
    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1">
      <p @class(['truncate', 'text-base font-semibold text-ink' => $value['isActive'], 'text-sm text-muted line-through' => ! $value['isActive']])>{{ $value['label'] }}</p>

      @unless ($value['isActive'])
        <x-badge>{{ __('no longer offered') }}</x-badge>
      @endunless

      <span class="ms-auto text-xs text-pretty text-muted">{{ $value['usageLabel'] }}</span>

      @if ($valueErrors->has('active'))
        <p id="{{ $rowId }}-active-error" role="alert" class="basis-full text-sm text-pretty text-error">{{ $valueErrors->first('active') }}</p>
      @endif

      <div class="flex shrink-0 items-center gap-0.5">
        <x-button.ghost size="small" :href="$value['editUrl']" :x-target="$rowId">{{ __('Edit') }}</x-button.ghost>

        @if ($value['isActive'] && $value['canBeDeleted'])
          <x-delete-action
            :title="__('Delete “:value”?', ['value' => $value['label']])"
            :confirm="__('Delete the value')"
            :action="route('settings.valueListItems.destroy', $value['id'])"
            :label="__('Delete')"
            variant="ghost"
            size="small"
            :x-target="$target"
          >
            <p>{{ __('Nothing uses it yet, so it is deleted for good and stops being offered anywhere.') }}</p>
          </x-delete-action>
        @elseif ($value['isActive'])
          <x-form method="put" :action="route('settings.valueListItemActivation.update', $value['id'])" :x-target="$target">
            <input type="hidden" name="active" value="0" />

            <x-button.ghost size="small">{{ __('Stop offering') }}</x-button.ghost>
          </x-form>
        @else
          <x-form method="put" :action="route('settings.valueListItemActivation.update', $value['id'])" :x-target="$target">
            <input type="hidden" name="active" value="1" />

            <x-button.ghost size="small" :aria-describedby="$valueErrors->has('active') ? $rowId.'-active-error' : null">{{ __('Offer again') }}</x-button.ghost>
          </x-form>
        @endif
      </div>
    </div>
  @endif
</x-sortable-row>
