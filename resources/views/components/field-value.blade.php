{{--
  What one field of a template reads as.

  It is the other half of <x-field-input> and exists for the same reason: a
  template is invented by the user, so what a type looks like cannot be decided
  per screen. A field has exactly two renderings, and both of them are promises
  the type keeps once, here and there.

  Most types read as a line of text and are handed straight to
  {@see \App\Helpers\FieldValueFormatter}, which is also what the header facts and
  the set-aside screen read through, so the same value never says two different
  things in two places. Four types need more than a line: a multiple choice is a
  set rather than a sentence, a yes or no is worth a mark you can see at a glance,
  a paragraph has to keep the shape it was typed in, and a type this product
  cannot yet fill in has to say so rather than read as empty.

  A field with nothing in it is drawn rather than left out. The template is the
  list of questions somebody decided to ask about this person, and a page that
  quietly hides the unanswered ones cannot be read as that list.

  @var \App\Models\Field $field
  @var \App\Models\FieldValue|null $value
  @var string|null $formatted
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
--}}
@props([
  'field',
  'value' => null,
  'formatted' => null,
  'rows' => [],
])

@php
  use App\Enums\FieldTypeEnum;

  $type = $field->type;

  /* A file is read through the app rather than from a public address, so the
     link is built here from the vault the file was kept in. */
  $held = $value?->file;
  $fileUrl = $held === null || $held->vault === null
      ? null
      : route('files.show', ['vault' => $held->vault, 'file' => $held]);

  $chosen = $type === FieldTypeEnum::Multiselect && $field->valueList
      ? $field->valueList->items()->whereIn('id', $value?->value_list_item_ids ?? [])->orderBy('position')->get()
      : collect();
@endphp

@switch(true)
  @case($type->isRepeater() && $rows !== [])
    {{-- Each repetition reads as what it holds, one under the other. A count
         would be shorter and would answer a question nobody has: somebody
         looking at a record wants the documents, not how many there are. --}}
    <div class="flex flex-col gap-2.5">
      @foreach ($rows as $row)
        <div class="flex flex-col gap-1.5 rounded-md border border-hairline bg-sand px-3.5 py-2.5">
          @foreach ($row['fields'] as $answer)
            <div class="flex flex-col gap-0.5">
              <span class="text-xs text-muted">{{ $answer['label'] }}</span>

              <x-field-value
                :field="$answer['field']"
                :value="$answer['rawValue']"
                :formatted="$answer['value']"
              />
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
    @break

  @case($type === FieldTypeEnum::Image && $fileUrl !== null)
    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="inline-block w-fit">
      <img
        src="{{ $fileUrl }}"
        alt="{{ $held->original_name }}"
        class="h-24 w-auto rounded-md border border-hairline object-cover"
      />
    </a>
    @break

  @case($type->holdsAFile() && $fileUrl !== null)
    <x-link :href="$fileUrl" target="_blank" rel="noopener">{{ $held->original_name }}</x-link>
    @break

  @case($type === FieldTypeEnum::Multiselect && $chosen->isNotEmpty())
    <div class="flex flex-wrap gap-1.5">
      @foreach ($chosen as $item)
        <span class="inline-flex h-6 items-center rounded-sm border border-hairline bg-sand px-2.25 text-xs text-ink-soft">
          {{ $item->label }}
        </span>
      @endforeach
    </div>
    @break

  @case($type === FieldTypeEnum::Boolean && $value?->value_boolean !== null)
    <span class="flex items-center gap-1.75 text-base text-ink">
      @if ($value->value_boolean)
        <x-icon name="check" :size="16" :stroke-width="1.9" class="text-success" />
        {{ __('Yes') }}
      @else
        <x-icon name="close" :size="16" :stroke-width="1.9" class="text-muted" />
        {{ __('No') }}
      @endif
    </span>
    @break

  @case($type === FieldTypeEnum::Textarea && $formatted !== null)
    {{-- A paragraph keeps the line breaks it was written with. Somebody who
         pressed return meant it, and running the whole thing together is the
         page deciding it knows better. --}}
    <p class="text-base whitespace-pre-line text-pretty text-ink">{{ $formatted }}</p>
    @break

  @case(! $type->hasAnInput() && $formatted === null)
    {{-- Nothing was written and nothing here could have written it. Saying
         "not recorded" would blame the reader for a gap the product left. --}}
    <span class="text-base text-placeholder">{{ __('This kind of field cannot be filled in here yet.') }}</span>
    @break

  @default
    <span @class(['text-base text-pretty', $formatted === null ? 'text-placeholder' : 'text-ink'])>
      {{ $formatted ?? __('Not recorded') }}
    </span>
@endswitch
