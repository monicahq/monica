{{-- What one field of a template reads as. --}}
{{--
  @var \App\Models\Field $field
  @var \App\Models\FieldValue|null $value
  @var string|null $formatted
  @var array<int, array{id: int, fields: array<int, array<string, mixed>>}> $rows
  @var string|null $reminder  what the reminder on this date does, in words, or null when there is none
--}}
@props([
  'field',
  'value' => null,
  'formatted' => null,
  'rows' => [],
  'reminder' => null,
])

@php
  use App\Enums\FieldTypeEnum;

  $type = $field->type;

  $held = $value?->file;
  $fileUrl = $held === null || $held->vault === null
      ? null
      : route('files.show', ['vault' => $held->vault, 'file' => $held]);

  $chosen = $type === FieldTypeEnum::Multiselect && $field->valueList
      ? $field->valueList->items->whereIn('id', $value?->value_list_item_ids ?? [])->sortBy('position')->values()
      : collect();

  $kind = $type->usesALabelList() ? ($value?->label_wording ?: $value?->valueListItem?->label) : null;
@endphp

@switch(true)
  @case($type->isRepeater() && $rows !== [])
    <div class="flex flex-col gap-2.5">
      @foreach ($rows as $row)
        <div class="flex flex-col gap-1.5 rounded-md border border-hairline bg-card px-3.5 py-2.5">
          @foreach ($row['fields'] as $answer)
            <div class="flex flex-col gap-0.5">
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
        <span class="inline-flex h-6 items-center rounded-full border border-hairline bg-card px-2.25 text-xs text-ink-soft">
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

  @case($type->usesALabelList() && $value?->value_string !== null)
    <span class="flex flex-wrap items-center gap-1.75">
      <span class="text-base text-ink">{{ $value->value_string }}</span>

      @if ($kind !== null)
        <x-badge>{{ $kind }}</x-badge>
      @endif
    </span>
    @break

  @case($type === FieldTypeEnum::Textarea && $formatted !== null)
    <p class="text-base whitespace-pre-line text-pretty text-ink">{{ $formatted }}</p>
    @break

  @case(! $type->hasAnInput() && $formatted === null)
    <span class="text-base text-placeholder">{{ __('This kind of field cannot be filled in here yet.') }}</span>
    @break

  @default
    <span @class([
      'text-base text-pretty',
      $formatted === null ? 'text-placeholder' : 'text-ink',
      'inline-flex items-center gap-2' => $reminder !== null,
    ])>
      {{ $formatted ?? __('Not recorded') }}

      @if ($reminder !== null)
        <x-tooltip :text="$reminder">
          <x-icon name="alarm" :size="15" class="text-muted" />
        </x-tooltip>
      @endif
    </span>
@endswitch
