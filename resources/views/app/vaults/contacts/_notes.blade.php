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
    @forelse ($field['notes']['entries'] as $entry)
      <div
        class="flex flex-col gap-3 border-t border-hairline-soft pt-5 first:border-t-0 first:pt-0 [&:not(:last-child)]:pb-5"
        @if ($entry['hidden'] !== []) x-data="{ expanded: false }" @endif
      >
        <div class="{{ $prose }}">
          @foreach ($entry['shown'] as $paragraph)
            <p class="whitespace-pre-line">{{ $paragraph }}</p>
          @endforeach
        </div>

        @if ($entry['hidden'] !== [])
          <div id="note-{{ $entry['id'] }}-rest" x-show="expanded" class="{{ $prose }}">
            @foreach ($entry['hidden'] as $paragraph)
              <p class="whitespace-pre-line">{{ $paragraph }}</p>
            @endforeach
          </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2 max-xs:flex-col-reverse max-xs:items-stretch max-xs:gap-3">
          <span class="text-xs text-muted">{{ $entry['addedLine'] }}</span>

          @if ($entry['hidden'] !== [])
            <button
              type="button"
              x-cloak
              @click="expanded = ! expanded"
              :aria-expanded="expanded.toString()"
              aria-controls="note-{{ $entry['id'] }}-rest"
              x-text="expanded ? @js(__('Show less')) : @js(__('Read more'))"
              class="focus-ring cursor-pointer rounded-sm text-sm font-medium text-ink underline decoration-hairline-strong underline-offset-2 hover:decoration-ink max-xs:min-h-11 max-xs:rounded-md max-xs:border max-xs:border-hairline-strong max-xs:bg-canvas max-xs:no-underline max-xs:shadow-control"
            >{{ __('Read more') }}</button>
          @endif
        </div>
      </div>
    @empty
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
    @endforelse
  </dd>
</div>
