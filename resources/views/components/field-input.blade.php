{{--
  The control one field of a template is filled in with.

  It exists so that the promise a type makes about itself is kept once. A field
  type is supposed to render accessibly by construction: the label bound to the
  input, the error announced, the required state programmatic rather than only
  visual. Templates are invented by the user, so that cannot be checked per
  screen; it has to be true of the type, here, and then true everywhere.

  Every control is named after the field it answers rather than after itself,
  since a page holds a set of answers keyed by field and the fields are whatever
  somebody invented this morning.

  A type with no control yet is not drawn as a broken input. It says what it
  holds and that this page cannot edit it, which is the truth and is more use
  than a box that would not save.

  This is the editing half of a pair. What the same field reads as when nobody is
  editing it is <x-field-value>, and the two are kept beside each other because a
  type that renders one way and reads another is a type that will drift.

  A choice is asked as the list itself rather than as a menu that hides it, and
  the shape of the mark says how many may be picked: round for one of a list,
  square for as many as apply. See <x-option-row>.

  A control names itself after the field it answers, and that is all it needs
  until the field is asked more than once. Inside a repetition the name has to
  say which repetition as well, so `id`, `name` and `yearName` can be given from
  outside; nothing but <x-field-repetitions> does.

  @var \App\Models\Field $field
  @var \App\Models\FieldValue|null $value
  @var string|null $formatted
  @var string|array<int, string>|null $error
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
  @var string|null $id
  @var string|null $name
  @var string|null $yearName
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
])

@php
  use App\Enums\FieldTypeEnum;

  $name ??= "fields[{$field->id}]";
  $id ??= "field-{$field->id}";
  $yearName ??= "year_is_unknown[{$field->id}]";
  $type = $field->type;

  /* A field that has left the structure keeps its value and stops being
     offered. Nothing on this page may write to it, and the screen that explains
     it is the one listing what was set aside. */
  $items = $type->usesValueList() && $field->valueList
      ? $field->valueList->items()->where('is_active', true)->orderBy('position')->get()
      : collect();

  $chosen = $value?->value_list_item_id;
  $chosenMany = $value?->value_list_item_ids ?? [];

  /*
   * A date control has to be given a year, and the year of a date nobody knows
   * the year of is a placeholder. Handing it back would put 1900 on the screen,
   * which is the one thing the storage exists to prevent, so the control is
   * filled with the current year instead: the day and the month are what
   * somebody typed and what comes back, the year is a placeholder either way,
   * and the save writes the stored one back the moment the box stays ticked.
   *
   * The placeholder year is not a leap year, so no 29th of February can be
   * stored under it and none can be lost coming back out.
   */
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
    {{-- An upload, and the promise that leaving the box alone leaves the file
         alone. It has to be said out loud: every other control on the page comes
         back holding what it holds, and this is the one that cannot, so without
         a sentence an empty box reads as an empty field. --}}
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
    {{-- The unticked box has to say so. Without the hidden field beside it a
         "no" and an unanswered question arrive identically, and the save cannot
         tell somebody who said no from somebody who said nothing. --}}
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
    {{-- One of a list, asked as the list. Every option is on the screen, the row
         is the target, and the round mark says only one of them may be picked.

         The way to answer nothing is an option of its own, because a group of
         radios cannot be un-picked once it has been touched. Leaving it out
         would mean somebody who ticked the wrong one has no way back to having
         said nothing at all. --}}
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
    {{-- Every option at once rather than a list somebody has to hold a key down
         for, which is the accessible reading of the same question. They are laid
         out as chips because a many-of-a-list is usually short words, and the
         square mark says as many as apply may be picked. --}}
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
    {{-- A date that comes round again may have a year nobody knows, which is a
         normal case rather than an error. The box beside it is the only way
         anybody can say so, and the year is never shown back to them. --}}
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
    </div>
    @break

  @case($type === FieldTypeEnum::Date)
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
    @break

  @case($type->hasAnInput())
    {{-- Everything left is one line of text, and the type only decides which
         keyboard a phone offers and what the browser checks before sending. --}}
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
