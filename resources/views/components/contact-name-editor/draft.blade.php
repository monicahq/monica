{{-- The editor for one way of writing a contact's name, opened from the name editor's list. --}}
{{--
  @var \App\ViewModels\Vaults\ContactNameEditorBlock $block
  @var bool $isNew
--}}
@props([
  'block',
  'isNew' => false,
])

<div data-name-draft class="flex flex-col gap-4 border-t border-hairline p-4 first:border-t-0" role="group" aria-labelledby="name-draft-title" @keydown.enter="commitOnEnter($event)">
  <p id="name-draft-title" class="{{ $isNew ? 'text-base font-semibold text-ink' : 'sr-only' }}">
    {{ $isNew ? __('Another way to write this name') : __('Edit this way of writing the name') }}
  </p>

  <div class="grid gap-4 sm:grid-cols-3">
    <x-select id="name-draft-kind" name="" x-model="draft.kind" @change="changeDraftKind()" :label="__('Type')">
      @foreach ($block->kinds() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </x-select>

    <x-select id="name-draft-language" name="" x-model="draft.language" :label="__('Language')" optional>
      <option value="">{{ __('Not specified') }}</option>
      @foreach ($block->languages() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </x-select>

    <x-select id="name-draft-script" name="" x-model="draft.script" :label="__('Writing system')" optional>
      <option value="">{{ __('Not specified') }}</option>
      @foreach ($block->scripts() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </x-select>
  </div>

  <fieldset x-show="draft.kind === 'phonetic'" class="max-w-90" :disabled="draft.kind !== 'phonetic'">
    <x-select id="name-draft-system" name="" x-model="draft.phonetic_system" :label="__('Pronunciation system')" :help="__('How the spelling should be read out.')">
      <option value="">{{ __('Choose a system') }}</option>
      @foreach ($block->phoneticSystems() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </x-select>
  </fieldset>

  <div x-show="draft.kind !== 'phonetic'" class="flex flex-col gap-1.5">
    <x-label for="name-draft-formatted" :value="__('Full name')" />
    <input
      id="name-draft-formatted"
      type="text"
      maxlength="255"
      x-model="draft.formatted_name"
      :placeholder="draftComposed"
      aria-describedby="name-draft-formatted-help"
      class="w-full rounded-md border border-hairline-strong bg-input px-3 py-2 font-sans text-ink placeholder:text-placeholder focus-ring-field"
    />
    <p id="name-draft-formatted-help" class="text-sm text-muted">{{ __('Written exactly as it should appear. Leave it empty to build it from the name parts.') }}</p>
  </div>

  <div class="flex flex-col gap-3.5">
    <x-button.ghost type="button" size="small" class="self-start" @click="showParts = ! showParts" ::aria-expanded="showParts" aria-controls="name-draft-parts">
      {{ __('Name parts') }}
      <x-slot:icon><span class="flex transition-transform motion-reduce:transition-none" :class="showParts && 'rotate-90'"><x-icon name="chevron-right" :size="16" /></span></x-slot:icon>
    </x-button.ghost>

    <div id="name-draft-parts" x-show="showParts" class="grid gap-4 sm:grid-cols-2">
      <x-input id="name-draft-given" name="" x-model="draft.given_name" :label="__('First name')" maxlength="255" />
      <x-input id="name-draft-family" name="" x-model="draft.family_name" :label="__('Last name')" maxlength="255" />
      <x-input id="name-draft-additional" name="" x-model="draft.additional_name" :label="__('Middle name')" optional maxlength="255" />
      <x-select id="name-draft-order" name="" x-model="draft.name_order" :label="__('Reads as')">
        @foreach ($block->nameOrders() as $value => $label)
          <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
      </x-select>
      <x-input id="name-draft-prefix" name="" x-model="draft.prefix" :label="__('Prefix')" optional maxlength="50" />
      <x-input id="name-draft-suffix" name="" x-model="draft.suffix" :label="__('Suffix')" optional maxlength="50" />
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-between gap-3">
    <p id="name-draft-hint" class="text-sm text-muted" aria-live="polite" x-text="draftHint"></p>
    <div class="flex gap-2">
      <x-button.secondary type="button" size="small" @click="cancelEditing()">{{ __('Cancel') }}</x-button.secondary>
      <x-button type="button" size="small" @click="commitDraft()" ::disabled="draftProblem !== ''" aria-describedby="name-draft-hint">{{ $isNew ? __('Add') : __('Done') }}</x-button>
    </div>
  </div>
</div>
