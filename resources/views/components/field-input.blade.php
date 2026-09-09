{{-- The control one field of a template is filled in with. --}}
{{--
  @var \App\Models\Field $field
  @var \App\Models\FieldValue|null $value
  @var string|null $formatted
  @var string|array<int, string>|null $error
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
  @var string|null $id
  @var string|null $name
  @var string|null $yearName
  @var array<string, mixed>|null $reminder
  @var string|null $reminderName
  @var \Illuminate\Support\ViewErrorBag|null $messages
--}}
@props([
  'field',
  'value' => null,
  'formatted' => null,
  'error' => null,
  'rows' => [],
  'id' => null,
  'name' => null,
  'yearName' => null,
  'reminder' => null,
  'reminderName' => null,
  'messages' => null,
])

@php
  use App\Enums\FieldTypeEnum;

  $name ??= "fields[{$field->id}]";
  $id ??= "field-{$field->id}";
  $yearName ??= "year_is_unknown[{$field->id}]";
  $reminderName ??= "reminders[{$field->id}]";
  $type = $field->type;

  $items = $type->usesValueList() && $field->valueList
      ? $field->valueList->items->where('is_active', true)->sortBy('position')->values()
      : collect();

  $chosen = $value?->value_list_item_id;
  $chosenMany = $value?->value_list_item_ids ?? [];

  $dateShown = $value?->value_date?->format('Y-m-d');

  if ($value?->value_date !== null && $value->year_is_unknown) {
      $dateShown = now()->format('Y').'-'.$value->value_date->format('m-d');
  }
@endphp

@switch(true)
  @case($type->isRepeater())
    <x-field-repetitions :field="$field" :rows="$rows" :error="$error" />
    @break

  @case($type->holdsAFile())
    <div class="flex flex-col gap-1.5">
      <x-file-input
        :id="$id"
        :name="$name"
        :label="$field->label"
        :accept="$field->acceptedMimeTypes() === [] ? null : implode(',', $field->acceptedMimeTypes())"
        :help="$field->help_text"
        :error="$error"
      />

      @if ($value?->file !== null)
        <p class="text-xs text-pretty text-muted">
          {{ __('Holding :name. Choosing another replaces it.', ['name' => $value->file->original_name]) }}
        </p>
      @endif
    </div>
    @break

  @case($type === FieldTypeEnum::Textarea)
    <x-textarea
      :id="$id"
      :name="$name"
      :label="$field->label"
      :value="$value?->value_text"
      :placeholder="$field->placeholder"
      :help="$field->help_text"
      :error="$error"
      :required="$field->is_required"
      :optional="! $field->is_required"
    />
    @break

  @case($type === FieldTypeEnum::Boolean)
    <div class="flex flex-col gap-1.5">
      <span class="flex items-baseline gap-1.5 text-label text-ink">{{ $field->label }}</span>

      <input type="hidden" name="{{ $name }}" value="0" />

      <x-option-row :id="$id" :name="$name" value="1" :checked="(bool) $value?->value_boolean">
        {{ $field->help_text ?? __('Yes') }}
      </x-option-row>

      <x-error :id="$error ? $id.'-error' : null" :messages="$error" />
    </div>
    @break

  @case($type === FieldTypeEnum::Radio)
    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">
        {{ $field->label }}

        @if (! $field->is_required)
          <span class="text-xs font-normal text-muted">{{ __('optional') }}</span>
        @endif
      </legend>

      @if ($field->help_text)
        <p class="text-xs text-muted">{{ $field->help_text }}</p>
      @endif

      <div class="grid gap-2 pt-0.5 sm:grid-cols-2">
        @foreach ($items as $item)
          <x-option-row
            type="radio"
            :id="$id.'-'.$item->id"
            :name="$name"
            :value="$item->id"
            :checked="$chosen === $item->id"
          >
            {{ $item->label }}
          </x-option-row>
        @endforeach

        @if (! $field->is_required)
          <x-option-row
            type="radio"
            :id="$id.'-none'"
            :name="$name"
            value=""
            :checked="$chosen === null"
          >
            {{ __('Not recorded') }}
          </x-option-row>
        @endif
      </div>

      <x-error :id="$error ? $id.'-error' : null" :messages="$error" />
    </fieldset>
    @break

  @case($type === FieldTypeEnum::Select)
    <x-select
      :id="$id"
      :name="$name"
      :label="$field->label"
      :help="$field->help_text"
      :error="$error"
      :required="$field->is_required"
      :optional="! $field->is_required"
    >
      <option value="">{{ __('Not recorded') }}</option>

      @foreach ($items as $item)
        <option value="{{ $item->id }}" @selected($chosen === $item->id)>{{ $item->label }}</option>
      @endforeach
    </x-select>
    @break

  @case($type === FieldTypeEnum::Multiselect)
    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">{{ $field->label }}</legend>

      @if ($field->help_text)
        <p class="text-xs text-muted">{{ $field->help_text }}</p>
      @endif

      <div class="flex flex-wrap gap-2 pt-0.5">
        @foreach ($items as $item)
          <x-option-row
            chip
            :id="$id.'-'.$item->id"
            :name="$name.'[]'"
            :value="$item->id"
            :checked="in_array($item->id, $chosenMany, false)"
          >
            {{ $item->label }}
          </x-option-row>
        @endforeach
      </div>

      <x-error :id="$error ? $id.'-error' : null" :messages="$error" />
    </fieldset>
    @break

  @case($type === FieldTypeEnum::RecurringDate)
    <div class="flex flex-col gap-1.5">
      <x-input
        type="date"
        :id="$id"
        :name="$name"
        :label="$field->label"
        :value="$dateShown"
        :help="$field->help_text"
        :error="$error"
        :required="$field->is_required"
        :optional="! $field->is_required"
      />

      <x-option-row
        :id="$id.'-year-unknown'"
        :name="$yearName"
        value="1"
        :checked="(bool) $value?->year_is_unknown"
        :hint="__('The day and the month are enough for a reminder.')"
      >
        {{ __('I do not know the year') }}
      </x-option-row>

      @if ($reminder !== null)
        <x-field-reminder :field="$field" :reminder="$reminder" :name="$reminderName" :id="$id.'-reminder'" :messages="$messages" />
      @endif
    </div>
    @break

  @case($type === FieldTypeEnum::Date)
    <div class="flex flex-col gap-1.5">
      <x-input
        type="date"
        :id="$id"
        :name="$name"
        :label="$field->label"
        :value="$dateShown"
        :help="$field->help_text"
        :error="$error"
        :required="$field->is_required"
        :optional="! $field->is_required"
      />

      @if ($reminder !== null)
        <x-field-reminder :field="$field" :reminder="$reminder" :name="$reminderName" :id="$id.'-reminder'" :messages="$messages" />
      @endif
    </div>
    @break

  @case($type->hasAnInput())
    <x-input
      :type="match ($type) {
          FieldTypeEnum::Number => 'number',
          FieldTypeEnum::Url => 'url',
          FieldTypeEnum::Email => 'email',
          FieldTypeEnum::Phone => 'tel',
          default => $field->is_sensitive ? 'password' : 'text',
      }"
      :id="$id"
      :name="$name"
      :label="$field->label"
      :value="$type === FieldTypeEnum::Number ? $value?->value_number : $value?->value_string"
      :placeholder="$field->placeholder"
      :help="$field->help_text"
      :error="$error"
      :required="$field->is_required"
      :optional="! $field->is_required"
      :allow-password-manager="! $field->is_sensitive"
    />
    @break

  @default
    <div class="flex flex-col gap-1">
      <span class="flex items-baseline gap-1.5 text-label text-ink">{{ $field->label }}</span>

      <p @class(['text-base text-pretty', $formatted ? 'text-ink' : 'text-placeholder'])>
        {{ $formatted ?? __('Not recorded') }}
      </p>

      <p class="text-xs text-muted">{{ __('This kind of field cannot be filled in here yet.') }}</p>
    </div>
@endswitch
