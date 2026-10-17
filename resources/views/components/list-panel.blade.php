{{-- A panel holding a list: the rows, what stands there when it holds nothing, and the way to ask for more. --}}
{{--
  @var \Illuminate\Support\Collection|array $items
  @var string|null $id
  @var string|null $target
  @var string|null $footerId
  @var string|null $nextPageUrl
  @var string|null $merge
  @var bool $showsEnd  draws the end of list line in place of Load more on the last page
  @var \Illuminate\Pagination\LengthAwarePaginator|null $paginator
  @var int|string|null $size
  @var int|null $default
  @var \Illuminate\View\ComponentSlot $empty
--}}
@props([
  'items',
  'id' => null,
  'target' => null,
  'footerId' => null,
  'nextPageUrl' => null,
  'merge' => null,
  'showsEnd' => false,
  'paginator' => null,
  'size' => null,
  'default' => null,
])

@php
  $holdsNothing = is_array($items) ? $items === [] : $items->isEmpty();
  $added = trans_choice(':count row added|:count rows added', count($items));
@endphp

<x-panel :flush="true" {{ $attributes }} x-data x-on:ajax:merged="$refs.announcement.textContent = $root.querySelector('[data-added]')?.dataset.added ?? ''">
  <p x-ref="announcement" aria-live="polite" class="sr-only"></p>

  <div @if ($id) id="{{ $id }}" @endif @if ($merge) x-merge="{{ $merge }}" @endif>
    @if ($holdsNothing)
      {{ $empty }}
    @else
      {{ $slot }}
    @endif
  </div>

  @if ($paginator && $paginator->total() > 0)
    <x-slot:footer :id="$footerId">
      <x-pagination :paginator="$paginator" :size="$size" :default="$default" :target="$target" />
    </x-slot:footer>
  @elseif ($nextPageUrl)
    <x-slot:footer :id="$footerId" :data-added="$added">
      <div class="text-center">
        <x-link :x-target="$target" :href="$nextPageUrl" x-autofocus>{{ __('Load more') }}</x-link>
      </div>
    </x-slot:footer>
  @elseif ($showsEnd)
    <x-slot:footer :id="$footerId" :data-added="$added">
      <p tabindex="-1" x-autofocus class="text-center text-muted focus-ring">{{ __('End of list') }}</p>
    </x-slot:footer>
  @endif
</x-panel>
