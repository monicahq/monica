{{-- A list of labels that becomes a box for a wording of your own when the open one is picked. --}}
{{--
  @var \Illuminate\Support\Collection<int, \App\Models\ValueListItem>|array<int, \App\Models\ValueListItem> $items
  @var string $id
  @var string $name
  @var string $wordingName
  @var int|null $chosen
  @var string|null $wording
  @var string|null $label
--}}
@props([
  'items',
  'id',
  'name',
  'wordingName',
  'chosen' => null,
  'wording' => null,
  'label' => null,
])

@php
  $offered = collect($items);
  $label ??= __('Type');
  $openItem = $offered->first(fn ($item) => $item->takesATypedWording());
@endphp

@if ($offered->isNotEmpty())
  <div
    {{ $attributes }}
    x-data="{
      picked: @js((string) $chosen),
      open: @js($openItem === null ? '' : (string) $openItem->id),
      get typing() { return this.open !== '' && this.picked === this.open },
    }"
  >
    <div x-show="! typing">
      <x-select :id="$id" :name="$name" :title="$label" :aria-label="$label" x-model="picked">
        <option value="">{{ __('Not recorded') }}</option>

        @foreach ($offered as $item)
          <option value="{{ $item->id }}" @selected($chosen === $item->id)>{{ $item->label }}</option>
        @endforeach
      </x-select>
    </div>

    @if ($openItem !== null)
      <div x-cloak x-show="typing" class="flex flex-col gap-1">
        <x-input
          :id="$id.'-wording'"
          :name="$wordingName"
          :title="$label"
          :aria-label="$label"
          :value="$wording"
          :placeholder="$openItem->label"
        />

        <button
          type="button"
          class="focus-ring self-start rounded-sm text-xs text-muted underline underline-offset-2 hover:text-ink"
          @click="picked = ''"
        >
          {{ __('Pick from the list') }}
        </button>
      </div>
    @endif
  </div>
@endif
