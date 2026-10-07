{{-- Every way a contact's name is written, the one it is shown under, and the editor for each. --}}
{{--
  @var \App\ViewModels\Vaults\ContactNameEditorBlock $block
  @var string $shortName
--}}
@props([
  'block',
  'shortName',
])

@php
  $config = [
    'rows' => $block->rows(),
    'display' => $block->display(),
    'hadName' => $block->hadAName(),
    'labels' => [
      'kinds' => $block->kinds(),
      'languages' => $block->languages(),
      'scripts' => $block->scripts(),
      'systems' => $block->phoneticSystems(),
      'unnamed' => __('Unnamed contact'),
      'showsAs' => __('Shows as :name'),
      'missingText' => __('Fill in the full name or at least one name part.'),
      'missingParts' => __('Fill in at least one name part.'),
      'missingSystem' => __('Choose a pronunciation system.'),
      'phoneticDisplay' => __('A pronunciation cannot be the name the contact is shown under.'),
      'editRow' => __('Edit :name'),
      'removeRow' => __('Remove :name'),
      'removal' => __('Remove :name? It is the display name, so choose which name to show instead. The other names stay.'),
      'removalBlocked' => __('Remove :name? It is the display name, and only pronunciations would be left, which cannot be shown instead. Add another way to write the name first.'),
    ],
  ];
  $nameErrors = collect(isset($errors) ? $errors->get('names*') : [])->flatten()->unique()->values();
@endphp

<div x-data="contactNameEditor(@js($config))" data-name-editor class="flex flex-col gap-6">
  <template x-for="(row, index) in posted" :key="index">
    <div hidden>
      <template x-for="key in postedKeys()" :key="key">
        <input type="hidden" :name="postedName(index, key)" :value="postedValue(row, key)" />
      </template>
    </div>
  </template>
  <input type="hidden" name="display" :value="postedDisplay" />
  <input type="hidden" :name="'names_included'" value="1" />

  @if ($nameErrors->isNotEmpty())
    <x-notice tone="danger" id="name-errors">
      @foreach ($nameErrors as $message)
        <p>{{ $message }}</p>
      @endforeach
    </x-notice>
  @endif

  <div x-show="single" class="grid gap-4 sm:grid-cols-2">
    <x-input id="name-prefix" name="" x-model="rows[0].prefix" :label="__('Prefix')" optional :placeholder="__('Dr, Mrs')" maxlength="50" />
    <x-input id="name-suffix" name="" x-model="rows[0].suffix" :label="__('Suffix')" optional :placeholder="__('Jr, PhD')" maxlength="50" />
    <x-input id="name-given" name="" x-model="rows[0].given_name" :label="__('First name')" maxlength="255" />
    <x-input id="name-additional" name="" x-model="rows[0].additional_name" :label="__('Middle name')" optional maxlength="255" />
    <x-input id="name-family" name="" x-model="rows[0].family_name" :label="__('Last name')" maxlength="255" />
    <x-select id="name-order" name="" x-model="rows[0].name_order" :label="__('Reads as')" optional>
      @foreach ($block->nameOrders() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </x-select>
  </div>

  <fieldset x-show="! single" x-cloak class="flex flex-col gap-3" aria-describedby="name-list-help">
    <legend class="text-sm font-semibold text-ink">{{ __('Name') }}</legend>
    <p id="name-list-help" class="text-sm text-pretty text-muted">{{ __('Ways of writing the same name. The one you select is shown in lists, page titles, notifications and initials.') }}</p>

    <div class="overflow-hidden rounded-lg border border-hairline">
      <template x-for="(row, index) in rows" :key="index">
        <div data-name-row class="border-b border-hairline last:border-b-0">
          <div class="flex items-center gap-3.5 px-4 py-3.5 has-checked:bg-card">
            <input
              type="radio"
              name="name-display-choice"
              form="name-display-choice"
              :id="`name-row-${index}`"
              :value="index"
              :checked="display === index"
              :aria-disabled="isPhonetic(row) ? 'true' : null"
              :aria-describedby="isPhonetic(row) ? `name-row-${index}-meta name-row-${index}-refusal` : `name-row-${index}-meta`"
              @click="chooseDisplay(index, $event)"
              class="size-4 shrink-0 accent-ink focus-ring"
            />

            <label :for="`name-row-${index}`" class="flex min-w-0 flex-1 cursor-pointer flex-col gap-0.5">
              <span class="flex flex-wrap items-center gap-2">
                <span class="text-base font-semibold text-ink" x-text="nameOf(row)"></span>
                <span x-show="display === index"><x-badge>{{ __('Display name') }}</x-badge></span>
              </span>
              <span :id="`name-row-${index}-meta`" class="text-sm text-muted" x-text="metaOf(row)"></span>
              <span x-show="isPhonetic(row)" :id="`name-row-${index}-refusal`" class="text-sm text-muted">{{ __('A pronunciation cannot be the name the contact is shown under.') }}</span>
            </label>

            <span class="flex shrink-0 gap-1">
              <x-button.ghost type="button" size="small" @click="startEditing(index)" ::disabled="busy" ::aria-label="rowLabel(labels.editRow, row)">{{ __('Edit') }}</x-button.ghost>
              <x-button.ghost type="button" size="small" @click="remove(index)" ::disabled="busy" ::aria-label="rowLabel(labels.removeRow, row)">{{ __('Remove') }}</x-button.ghost>
            </span>
          </div>

          <div x-show="confirming === index" x-cloak data-name-removal class="flex flex-col gap-3 border-t border-hairline bg-card px-4 py-3.5 sm:pl-11.5" role="group" :aria-labelledby="`name-removal-${index}`">
            <p :id="`name-removal-${index}`" class="text-sm text-ink" x-text="removalPrompt(row)"></p>

            <div x-show="replacement !== null" class="max-w-80">
              <label :for="`name-replacement-${index}`" class="sr-only">{{ __('Name to show instead') }}</label>
              <select :id="`name-replacement-${index}`" x-model.number="replacement" class="w-full rounded-md border border-hairline-strong bg-input px-3 py-2 text-ink focus-ring-field">
                <template x-for="(other, otherIndex) in rows" :key="otherIndex">
                  <option :hidden="otherIndex === index || isPhonetic(other)" :disabled="otherIndex === index || isPhonetic(other)" :value="otherIndex" x-text="nameOf(other)" :selected="otherIndex === replacement"></option>
                </template>
              </select>
            </div>

            <div class="flex gap-2">
              <x-button.danger type="button" size="small" x-show="replacement !== null" @click="confirmRemoval()">{{ __('Remove and switch') }}</x-button.danger>
              <x-button.secondary type="button" size="small" data-name-keep @click="cancelRemoval()">{{ __('Keep it') }}</x-button.secondary>
            </div>
          </div>

          <template x-if="editing === index">
            <x-contact-name-editor.draft :block="$block" :is-new="false" />
          </template>
        </div>
      </template>

      <template x-if="editing === 'new'">
        <x-contact-name-editor.draft :block="$block" :is-new="true" />
      </template>
    </div>
  </fieldset>

  <div x-show="editing === null && confirming === null" class="flex flex-col items-start gap-1.5">
    <x-button.secondary type="button" size="small" data-name-add @click="startAdding()">
      {{ __('Add another way to write this name') }}
      <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
    </x-button.secondary>
    <p x-show="single" x-cloak class="text-sm text-muted">{{ __('For a name in another writing system, a transliteration or how it is pronounced.') }}</p>
    <p x-show="! single" x-cloak class="text-sm text-muted">{{ __('Searching for any of these names finds :name.', ['name' => $shortName]) }}</p>
  </div>

  <div x-show="warnsOfNoName" x-cloak role="alert" class="flex flex-col gap-3">
    <x-notice tone="warning">{{ __('Every part of the name is empty. Saving shows them as Unnamed contact everywhere.') }}</x-notice>
    <div class="flex gap-2">
      <x-button.danger type="button" size="small" @click="sendWithoutName()">{{ __('Save without a name') }}</x-button.danger>
      <x-button.secondary type="button" size="small" @click="warnsOfNoName = false">{{ __('Keep editing') }}</x-button.secondary>
    </div>
  </div>
</div>
