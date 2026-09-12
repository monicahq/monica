{{-- A panel holding a list: the rows, what stands there when it holds nothing, and the way to ask for more. --}}
{{--
  @var \Illuminate\Support\Collection|array $items
  @var string|null $id
  @var string|null $target
  @var string|null $footerId
  @var string|null $nextPageUrl
  @var \Illuminate\View\ComponentSlot $empty
--}}
@props([
  'items',
  'id' => null,
  'target' => null,
  'footerId' => null,
  'nextPageUrl' => null,
  'merge' => null,
])

@php
  $holdsNothing = is_array($items) ? $items === [] : $items->isEmpty();
@endphp

<x-panel :flush="true" {{ $attributes }}>
  <div @if ($id) id="{{ $id }}" @endif @if ($merge) x-merge="{{ $merge }}" @endif>
    @if ($holdsNothing)
      {{ $empty }}
    @else
      {{ $slot }}
    @endif
  </div>

  @if ($nextPageUrl)
    <x-slot:footer :id="$footerId">
      <div class="text-center">
        <x-link :x-target="$target" :href="$nextPageUrl">{{ __('Load more') }}</x-link>
      </div>
    </x-slot:footer>
  @endif
</x-panel>
