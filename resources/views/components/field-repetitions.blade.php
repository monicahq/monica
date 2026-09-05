{{-- The control for a field the template says there may be several of. --}}
{{--
  @var \App\Models\Field $field
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
  @var string|array<int, string>|null $error
--}}
@props([
  'field',
  'rows' => [],
  'error' => null,
])

@php
  $atATime = 3;

  $children = $field->children->filter(
      fn (\App\Models\Field $child): bool => $child->type->hasAnInput() && ! $child->type->isRepeater(),
  );

  $room = $field->repeat_max === null ? $atATime : max(0, $field->repeat_max - count($rows));
  $blanks = min($atATime, $room);

  $mayRemove = count($rows) > ($field->repeat_min ?? 0);

  $repetition = 'flex flex-col gap-4 rounded-md border border-hairline bg-canvas p-3.5';

  $atTheFront = $field->repetition_order?->addsToTheFront() ?? false;

  $rowOffset = $atTheFront ? $blanks : 0;
  $blankOffset = $atTheFront ? 0 : count($rows);
@endphp

<fieldset class="flex flex-col gap-2" x-data="{ shown: 0 }">
  <legend class="text-label text-ink">{{ $field->label }}</legend>

  @if ($field->help_text)
    <p class="text-xs text-muted">{{ $field->help_text }}</p>
  @endif

  <div class="flex flex-col gap-2.5 pt-0.5">
    @foreach ($atTheFront ? ['blanks', 'held'] : ['held', 'blanks'] as $part)
      @if ($part === 'held')
      @foreach ($rows as $index => $row)
        <div x-data="{ removed: false }">
          <fieldset x-show="! removed" :disabled="removed" class="{{ $repetition }}">
            <input type="hidden" name="fields[{{ $field->id }}][{{ $rowOffset + $index }}][id]" value="{{ $row['id'] }}" />

            @foreach ($row['fields'] as $answer)
              <x-field-input
                :field="$answer['field']"
                :value="$answer['rawValue']"
                :formatted="$answer['value']"
                :id="'field-'.$field->id.'-'.$index.'-'.$answer['id']"
                :name="'fields['.$field->id.']['.($rowOffset + $index).'][values]['.$answer['id'].']'"
                :year-name="'fields['.$field->id.']['.($rowOffset + $index).'][unknown_year]['.$answer['id'].']'"
              />
            @endforeach

            @if ($mayRemove)
              <div class="flex">
                <x-button.ghost type="button" size="small" class="ml-auto" @click="removed = true">
                  <x-icon name="close" :size="14" :stroke-width="1.8" />
                  {{ __('Remove') }}
                </x-button.ghost>
              </div>
            @endif
          </fieldset>

          <p x-show="removed" x-cloak class="flex flex-wrap items-center gap-2 rounded-md border border-dashed border-hairline-strong bg-card px-3.5 py-2.5 text-xs text-muted">
            {{ __('This one goes when you save the section.') }}

            <button
              type="button"
              @click="removed = false"
              class="cursor-pointer font-medium text-ink underline underline-offset-2 decoration-hairline-strong hover:decoration-ink"
            >{{ __('Keep it') }}</button>
          </p>
        </div>
      @endforeach
      @else
      @for ($blank = 0; $blank < $blanks; $blank++)
        <template x-if="shown > {{ $blank }}">
          <fieldset class="{{ $repetition }}">
            @foreach ($children as $child)
              <x-field-input
                :field="$child"
                :id="'field-'.$field->id.'-new'.$blank.'-'.$child->id"
                :name="'fields['.$field->id.']['.($blankOffset + $blank).'][values]['.$child->id.']'"
                :year-name="'fields['.$field->id.']['.($blankOffset + $blank).'][unknown_year]['.$child->id.']'"
              />
            @endforeach
          </fieldset>
        </template>
      @endfor
      @endif
    @endforeach
  </div>

  @if ($blanks > 0)
    <div class="flex flex-wrap items-center gap-2.5">
      <x-button.secondary type="button" size="small" x-show="shown < {{ $blanks }}" @click="shown++">
        <x-icon name="plus" :size="14" :stroke-width="1.8" />
        {{ __('Add another') }}
      </x-button.secondary>

      <p x-show="shown >= {{ $blanks }}" x-cloak class="text-xs text-muted">
        {{ __('Save the section to add more.') }}
      </p>
    </div>
  @elseif ($field->repeat_max !== null)
    <p class="text-xs text-muted">
      {{ __('This is as many as this field takes.') }}
    </p>
  @endif

  <x-error :id="$error ? 'field-'.$field->id.'-error' : null" :messages="$error" />
</fieldset>
