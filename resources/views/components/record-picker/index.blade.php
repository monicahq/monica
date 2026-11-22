{{-- A search for one record of the vault, the one chosen, and the hidden value the form sends. --}}
{{--
  @var string $name
  @var string $id
  @var string $url
  @var string $label
  @var string|null $chosen
  @var string|null $chosenName
  @var string|array<int, string>|null $error
  @var bool $compact
  @var string|null $placeholder
--}}
@props([
  'name',
  'id',
  'url',
  'label',
  'chosen' => null,
  'chosenName' => null,
  'error' => null,
  'compact' => false,
  'placeholder' => null,
])

@php
  $listId = "{$id}-choices";
  $queryName = "{$id}_query";
@endphp

@if ($compact)
  <div
    x-data="recordPicker({ url: @js($url), listId: @js($listId), chosen: @js((string) $chosen), chosenName: @js((string) $chosenName) })"
    class="relative"
  >
    <input type="hidden" name="{{ $name }}" x-model="chosen" x-bind:disabled="! hasChosen">

    <button
      type="button"
      x-cloak
      x-show="hasChosen"
      x-ref="change"
      x-on:click="forget(); $nextTick(() => $refs.search.focus())"
      class="focus-ring flex h-8 items-center gap-1.5 rounded-md border border-hairline-strong bg-input ps-3 pe-2 text-sm text-ink hover:border-muted-soft"
    >
      <span>{{ $label }} <span x-text="chosenName"></span></span>
      <span class="sr-only">{{ __('Choose another') }}</span>
      <x-icon name="close" :size="14" class="text-muted" />
    </button>

    <div x-show="! hasChosen" x-data="{ listOpen: false }" x-on:click.outside="listOpen = false" x-on:keydown.escape="listOpen = false">
      <x-input
        type="search"
        :id="$id"
        size="small"
        class="w-56"
        :placeholder="$placeholder ?? __('Type a name')"
        aria-label="{{ $label }}"
        x-ref="search"
        x-model="query"
        x-on:focus="listOpen = true; open()"
        x-on:input.debounce.400ms="listOpen = true; search()"
        x-on:keydown.enter.prevent="search()"
        aria-controls="{{ $listId }}"
        autocomplete="off"
      />

      <div x-show="listOpen" x-cloak class="absolute start-0 top-full z-20 mt-1 w-72 rounded-md bg-canvas shadow-pop">
        <x-record-picker.candidates :id="$listId" :candidates="[]" />
      </div>
    </div>
  </div>
@else
  <div
    x-data="recordPicker({ url: @js($url), listId: @js($listId), chosen: @js((string) $chosen), chosenName: @js((string) $chosenName) })"
    class="flex flex-col gap-2"
  >
    <input type="hidden" name="{{ $name }}" x-model="chosen">

    <div x-cloak x-show="hasChosen" class="flex flex-col gap-1.5">
      <p id="{{ $id }}-label" class="text-label text-ink">{{ $label }}</p>

      <div class="flex items-center gap-2.5 rounded-md border border-hairline bg-sunken px-3 py-2">
        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink" aria-live="polite" x-text="chosenName"></span>

        <x-button.ghost size="small" type="button" x-ref="change" @click="forget(); $nextTick(() => $refs.search.focus())" aria-describedby="{{ $id }}-label">
          {{ __('Choose another') }}
        </x-button.ghost>
      </div>
    </div>

    <div x-show="! hasChosen" class="flex flex-col gap-2">
      <x-input
        type="search"
        :id="$id"
        :name="$queryName"
        :label="$label"
        :error="$error"
        :placeholder="__('Type a name')"
        x-ref="search"
        x-model="query"
        x-on:focus="open()"
        x-on:input.debounce.400ms="search()"
        x-on:keydown.enter.prevent="search()"
        aria-controls="{{ $listId }}"
        autocomplete="off"
      />

      <x-record-picker.candidates :id="$listId" :candidates="[]" />
    </div>
  </div>
@endif
