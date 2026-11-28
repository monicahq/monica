{{-- One thing a life event of this nature is asked for: what it is called, what kind of answer it takes, and how it is checked. --}}
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

    <span data-field-mark aria-hidden="true" class="flex size-7.5 shrink-0 items-center justify-center rounded-md border border-hairline bg-card text-body">
      @isset($marks[$field['type']])
        <span data-kind="{{ $field['type'] }}" x-show="type === @js($field['type'])" class="flex">
          <x-icon :name="$marks[$field['type']]" :size="15" :stroke-width="1.5" />
        </span>
      @endisset
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
        @change="pickKind($el)"
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

  <div class="flex flex-col gap-2.5 ps-11">
    {{-- Several kinds of field name the same setting, so only the settings of the kind that is picked are left switched on to be sent. --}}
    <div data-field-settings class="contents">
      @if (($settingsByType[$field['type']] ?? []) !== [])
        @include('app.settings.account.life-event-types._settings', [
          'reference' => $reference,
          'kind' => $field['type'],
          'settings' => $settingsByType[$field['type']],
          'values' => $field['settings'],
          'chosen' => true,
        ])
      @endif
    </div>

    <fieldset
      x-cloak
      x-show="@js($choiceTypes).includes(type)"
      :disabled="! @js($choiceTypes).includes(type)"
      @disabled(! $offersChoices)
      class="m-0 flex min-w-0 flex-col gap-1.5 border-0 p-0"
    >
      <legend class="sr-only">{{ __('Choices offered') }}</legend>

      <x-caption>{{ __('Choices offered') }}</x-caption>

      <div data-choices class="flex flex-col gap-1.5">
        @foreach ($field['choices'] as $choice)
          @include('app.settings.account.life-event-types._choice', ['reference' => $reference, 'choice' => $choice])
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
