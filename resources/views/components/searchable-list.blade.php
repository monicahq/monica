{{-- A button unfolding a searchable list in named groups, run by a picker built on searchable-list.js. --}}
{{--
  @var string $id
  @var string $labelId
  @var string $searchLabel
  @var string $width
  @var bool $invalid
  @var string $describedBy
  @var \Illuminate\View\ComponentSlot $trigger
  @var \Illuminate\View\ComponentSlot $option
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
@props([
  'id',
  'labelId',
  'searchLabel',
  'width' => 'fixed',
  'invalid' => false,
  'describedBy' => '',
  'footer' => null,
])

@php
  $popupWidth = match ($width) {
    'trigger' => '',
    default => 'w-80 max-w-[calc(100vw-2rem)]',
  };
@endphp

<div
  @click.outside="close(false)"
  @focusout="if ($event.relatedTarget !== null && ! $el.contains($event.relatedTarget)) close(false)"
  @keydown.escape="if (open) { $event.stopPropagation(); $event.preventDefault(); close() }">
  <button
    id="{{ $id }}"
    type="button"
    x-ref="trigger"
    aria-haspopup="listbox"
    aria-expanded="false"
    :aria-expanded="open ? 'true' : 'false'"
    aria-labelledby="{{ $labelId }} {{ $id }}"
    @if ($invalid) aria-invalid="true" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @click="toggle()"
    @keydown.arrow-down.prevent="open ? $refs.search.focus() : toggle()"
    {{ $trigger->attributes }}
  >
    {{ $trigger }}
  </button>

  <div
    x-show="open"
    x-cloak
    x-anchor.fixed.bottom-start.offset.6="$refs.trigger"
    @if ($width === 'trigger') :style="open ? { width: `${$refs.trigger.offsetWidth}px` } : {}" @endif
    x-transition:enter="transition ease-out duration-(--duration-fast)"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-(--duration-fast)"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="{{ $popupWidth }} z-30 flex flex-col rounded-lg border border-hairline bg-canvas shadow-pop"
  >
    <div class="flex h-11 items-center gap-2 rounded-t-lg border-b border-hairline px-3 has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45 has-focus-visible:ring-inset">
      <x-icon name="search" :size="15" class="text-muted" />

      <input
        type="text"
        x-ref="search"
        x-model="query"
        @input="search()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.home.prevent="moveTo('first')"
        @keydown.end.prevent="moveTo('last')"
        @keydown.enter.prevent="pickActive()"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="true"
        aria-controls="{{ $id }}-listbox"
        :aria-activedescendant="active === '' ? null : active"
        aria-label="{{ $searchLabel }}"
        placeholder="{{ $searchLabel }}"
        autocomplete="off"
        class="h-full min-w-0 flex-1 border-0 bg-transparent text-[16px] text-ink outline-none sm:text-sm"
      />

      <span class="hidden text-xs text-muted sm:inline" aria-hidden="true">{{ __('Esc to close') }}</span>
    </div>

    <ul id="{{ $id }}-listbox" role="listbox" aria-labelledby="{{ $labelId }}" tabindex="-1" class="max-h-[min(18rem,40dvh)] overflow-y-auto overscroll-contain p-1.5">
      <template x-for="group in groups" :key="group.key">
        <li role="presentation" class="flex flex-col gap-px pb-1.5">
          <template x-if="labels[group.key]">
            <x-caption class="px-2 pt-2 pb-1" aria-hidden="true"><span x-text="labels[group.key]"></span></x-caption>
          </template>

          <ul :role="labels[group.key] ? 'group' : 'none'" :aria-label="labels[group.key] ?? null">
            <template x-for="option in group.items" :key="group.key + option.value">
              <li
                role="option"
                :id="optionId(group.key, option.value)"
                :aria-selected="active === optionId(group.key, option.value) ? 'true' : 'false'"
                @click="pick(option.value)"
                @mousemove="active = optionId(group.key, option.value)"
                class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-left text-sm text-ink aria-selected:bg-hover"
              >
                {{ $option }}
              </li>
            </template>
          </ul>
        </li>
      </template>
    </ul>

    {{ $footer }}
  </div>
</div>
