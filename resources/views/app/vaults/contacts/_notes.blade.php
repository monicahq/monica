{{-- A field of notes, read as text across the width of its section. --}}
{{--
  @var array<string, mixed> $field
  @var array<string, mixed> $section
--}}
@php
  $prose = 'flex max-w-[68ch] flex-col gap-3 text-[14.5px]/[1.7] break-words text-pretty text-ink';
@endphp

<div class="flex flex-col gap-2 border-b border-hairline px-6 py-5 last:border-b-0 max-xs:px-4 max-xs:py-4">
  <dt @class([
    'text-sm font-semibold text-ink-soft' => $field['notes']['showsLabel'],
    'sr-only' => ! $field['notes']['showsLabel'],
  ])>{{ $field['label'] }}</dt>

  <dd class="m-0 flex flex-col">
    @if ($field['notes']['entries'] !== [])
      @include('app.vaults.contacts._field-rows', [
        'field' => $field['field'],
        'rows' => [],
        'notes' => $field['notes']['entries'],
        'more' => $field['rowsMore'],
      ])
    @else
      <div class="flex flex-wrap items-center justify-between gap-3 max-xs:flex-col max-xs:items-stretch">
        <span class="text-base text-muted">{{ __('No note yet') }}</span>

        @if ($field['isEditable'] && $section['isEditable'])
          <x-button.secondary
            size="small"
            :href="$section['editUrl']"
            :x-target="$section['regionId']"
            class="max-xs:w-full"
          >
            <x-slot:icon><x-icon name="plus" :size="15" /></x-slot:icon>
            {{ __('Add a note') }}
          </x-button.secondary>
        @endif
      </div>
    @endif
  </dd>
</div>
