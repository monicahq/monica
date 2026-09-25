{{-- A search for one record of the vault, the one chosen, and the hidden value the form sends. --}}
{{--
  @var string $name
  @var string $id
  @var string $url
  @var string $label
  @var string|null $chosen
  @var string|null $chosenName
  @var string|array<int, string>|null $error
--}}
@props([
  'name',
  'id',
  'url',
  'label',
  'chosen' => null,
  'chosenName' => null,
  'error' => null,
])

@php
  $listId = "{$id}-choices";
@endphp

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
