{{-- One thing an activity of this nature is asked for: what it is called, what kind of answer it takes, and how it is checked. --}}
{{--
  @var array<string, mixed> $field
  @var array<int, array{name: string, types: array<int, array{key: string, label: string}>}> $families
  @var array<string, string> $marks
  @var array<string, list<array<string, mixed>>> $settingsByType
  @var array<int, string> $choiceTypes
--}}
@php
  $reference = $field['reference'];
  $id = 'field-'.$reference;
  $offersChoices = in_array($field['type'], $choiceTypes, true);
@endphp
<div
  data-field
  data-reference="{{ $reference }}"
  x-data="{ type: @js($field['type']) }"
  draggable="true"
  @dragstart="startField($event, $el)"
  @dragover.prevent="overField($event, $el)"
  @drop.prevent="dropField($event, $el)"
  @dragend="stopDragging()"
  class="flex flex-col gap-2.5 border-b border-hairline px-3 py-3 last:border-b-0"
>
  <input type="hidden" name="fields[{{ $reference }}][key]" value="{{ $field['key'] }}" />
  <input type="hidden" name="fields[{{ $reference }}][group]" value="{{ $field['group'] }}" data-field-group />
  <input type="hidden" name="fields[{{ $reference }}][position]" value="0" data-field-position />

  <div class="flex flex-wrap items-center gap-2">
    <span title="{{ __('Drag to reorder') }}" aria-hidden="true" class="flex shrink-0 cursor-grab p-1 text-placeholder active:cursor-grabbing">
      <x-icon name="grip" :size="15" :stroke-width="1.5" />
    </span>

    <span aria-hidden="true" class="flex size-7.5 shrink-0 items-center justify-center rounded-md border border-hairline bg-card text-body">
      @foreach ($marks as $key => $mark)
        <span x-show="type === @js($key)" @if ($field['type'] !== $key) style="display: none" @endif class="flex">
          <x-icon :name="$mark" :size="15" :stroke-width="1.5" />
        </span>
      @endforeach
    </span>

    <div class="min-w-45 flex-1">
      <x-input
        :id="$id.'-label'"
        :name="'fields['.$reference.'][label]'"
        :value="$field['label']"
        :aria-label="__('Name of the field')"
        :placeholder="__('Name of the field')"
        size="small"
        maxlength="255"
        required
      />
    </div>

    <div class="w-full min-w-45 sm:w-52">
      <x-select
        :id="$id.'-type'"
        :name="'fields['.$reference.'][type]'"
        :aria-label="__('Kind of answer it takes')"
        size="small"
        x-model="type"
        required
      >
        @foreach ($families as $family)
          <optgroup label="{{ $family['name'] }}">
            @foreach ($family['types'] as $type)
              <option value="{{ $type['key'] }}" @selected($field['type'] === $type['key'])>{{ $type['label'] }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </x-select>
    </div>

    <label class="flex shrink-0 cursor-pointer items-center gap-1.5">
      <input
        type="checkbox"
        name="fields[{{ $reference }}][is_required]"
        value="1"
        @checked($field['isRequired'])
        class="peer sr-only"
      />

      <span aria-hidden="true" class="flex size-4 items-center justify-center rounded-xs border border-hairline-strong bg-input text-transparent transition-colors duration-(--duration-fast) peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-[3px] peer-focus-visible:ring-focus-ring/45">
        <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
        </svg>
      </span>

      <span class="text-xs text-ink">{{ __('Required') }}</span>
    </label>

    <span class="flex shrink-0 items-center gap-1">
      <x-icon-button type="button" icon="chevron-up" :label="__('Move this field up')" @click="moveField($el, -1)" />
      <x-icon-button type="button" icon="chevron-down" :label="__('Move this field down')" @click="moveField($el, 1)" />
      <x-icon-button type="button" icon="close" :label="__('Remove this field')" @click="removeField($el)" />
    </span>
  </div>

  <div class="flex flex-col gap-2.5 pl-11">
    {{-- Several kinds of field name the same setting, so only the settings of the kind that is picked are left switched on to be sent. --}}
    @foreach ($settingsByType as $kind => $settings)
      @continue($settings === [])

      @php
        $chosen = $field['type'] === $kind;
      @endphp
      <fieldset
        x-cloak
        x-show="type === @js($kind)"
        :disabled="type !== @js($kind)"
        @if ($chosen) style="display: flex" @endif
        @disabled(! $chosen)
        class="m-0 flex min-w-0 flex-wrap items-end gap-2.5 border-0 p-0"
      >
        <legend class="sr-only">{{ __('How this field is checked') }}</legend>

        @foreach ($settings as $setting)
          @php
            $settingId = $id.'-'.$kind.'-'.$setting['key'];
            $value = $chosen ? ($field['settings'][$setting['key']] ?? '') : '';
          @endphp
          <div @class(['flex flex-col gap-1', match ($setting['width']) {
            'narrow' => 'w-24',
            'medium' => 'w-40',
            default => 'w-60 max-w-full',
          }])>
            <x-caption>{{ $setting['label'] }}</x-caption>

            @if ($setting['control'] === 'select')
              <x-select
                :id="$settingId"
                :name="'fields['.$reference.'][settings]['.$setting['key'].']'"
                :aria-label="$setting['label']"
                size="small"
              >
                @foreach ($setting['options'] as $option)
                  <option value="{{ $option['value'] }}" @selected($value === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
              </x-select>
            @else
              <x-input
                :id="$settingId"
                :name="'fields['.$reference.'][settings]['.$setting['key'].']'"
                :type="$setting['control'] === 'number' ? 'number' : 'text'"
                :value="$value"
                :aria-label="$setting['label']"
                :placeholder="$setting['placeholder']"
                size="small"
                maxlength="255"
              />
            @endif
          </div>
        @endforeach
      </fieldset>
    @endforeach

    <fieldset
      x-cloak
      x-show="@js($choiceTypes).includes(type)"
      :disabled="! @js($choiceTypes).includes(type)"
      @if ($offersChoices) style="display: flex" @endif
      @disabled(! $offersChoices)
      class="m-0 flex min-w-0 flex-col gap-1.5 border-0 p-0"
    >
      <legend class="sr-only">{{ __('Choices offered') }}</legend>

      <x-caption>{{ __('Choices offered') }}</x-caption>

      <div data-choices class="flex flex-col gap-1.5">
        @foreach ($field['choices'] as $choice)
          @include('app.settings.account.activity-types._choice', ['reference' => $reference, 'choice' => $choice])
        @endforeach
      </div>

      <button
        type="button"
        @click="addChoice($el)"
        class="cursor-pointer self-start text-xs text-link underline underline-offset-2 transition-colors hover:text-link-hover focus-ring"
      >{{ __('Add a choice') }}</button>
    </fieldset>

    <div class="max-w-105">
      <x-input
        :id="$id.'-help'"
        :name="'fields['.$reference.'][help_text]'"
        :value="$field['helpText']"
        :aria-label="__('Help text shown under the field')"
        :placeholder="__('Help text shown under the field (optional)')"
        size="small"
        maxlength="255"
      />
    </div>
  </div>
</div>
