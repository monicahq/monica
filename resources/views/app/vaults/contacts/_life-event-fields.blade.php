{{-- The custom fields of the life event type being recorded, each opened only when somebody asks for it. --}}
{{--
  @var \App\Models\LifeEventType|null $lifeEventType
  @var array<int, array{id: int, label: string, field: \App\Models\Field, rawValue: \App\Models\FieldValue|null, hasOldValue: bool}> $fields
  @var \App\Models\Vault $vault
--}}
<div id="life-event-custom-fields" x-merge="replace" class="flex flex-col gap-3.5">
  @foreach ($fields as $field)
    <fieldset
      data-custom-field
      data-piece="field-{{ $field['id'] }}"
      data-label="{{ $field['label'] }}"
      @if ($field['hasOldValue']) x-init="openPiece('field-{{ $field['id'] }}')" @endif
      x-show="isOpen('field-{{ $field['id'] }}')"
      x-bind:disabled="! isOpen('field-{{ $field['id'] }}')"
      x-cloak
      class="relative"
    >
      @if ($field['field']->type->holdsAFile())
        <input type="hidden" name="kept_files[]" value="{{ $field['id'] }}" />
      @endif

      <x-field-input
        :field="$field['field']"
        :value="$field['rawValue']"
        :reminder="null"
        :messages="$errors"
        :error="$errors->get('fields.'.$field['id'])"
        :vault="$vault"
      />

      <button type="button" x-on:click="closePiece('field-{{ $field['id'] }}')" aria-label="{{ __('Remove :name', ['name' => $field['label']]) }}" class="focus-ring absolute top-0 right-0 rounded-sm text-xs font-medium text-muted hover:text-ink hover:underline">{{ __('Remove') }}</button>
    </fieldset>
  @endforeach

  @if ($lifeEventType && $fields === [])
    <p class="flex items-start gap-2.5 rounded-lg border border-dashed border-hairline-strong p-3 text-sm text-pretty text-body">
      <span class="pt-0.5 text-muted" aria-hidden="true"><x-icon name="info" :size="15" /></span>
      <span>
        {{ __(':type asks for nothing beyond the date and the people.', ['type' => $lifeEventType->name]) }}
        <x-link :href="route('settings.lifeEventTypes.edit', ['lifeEventType' => $lifeEventType])">{{ __('Add fields to this type') }}</x-link>
      </span>
    </p>
  @endif

  @if ($fields !== [])
    <div class="flex flex-wrap gap-2">
      @foreach ($fields as $field)
        <button
          type="button"
          x-show="! isOpen('field-{{ $field['id'] }}')"
          x-on:click="openPiece('field-{{ $field['id'] }}')"
          class="focus-ring inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-full border border-hairline bg-canvas px-3 text-sm font-medium text-body hover:bg-hover"
        >
          <x-icon name="plus" :size="15" />
          {{ $field['label'] }}
        </button>
      @endforeach
    </div>
  @endif
</div>
